<?php

namespace App\Services\Mdm;

use App\Enums\EnrollmentStatus;
use App\Enums\ManagementStatus;
use App\Enums\MdmCommandStatus;
use App\Enums\MdmCommandType;
use App\Enums\MdmEngineType;
use App\Models\Device;
use App\Models\DeviceEvent;
use App\Models\DeviceToken;
use App\Models\Enrollment;
use App\Models\MdmCommand;
use App\Models\Organization;
use App\Support\ApplePlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NanoMdmWebhookService
{
    public const LAST_WEBHOOK_CACHE_KEY = 'nanomdm.last_webhook_at';

    public function __construct(private MdmCommandService $commands) {}

    public function verifyHmac(Request $request): bool
    {
        $secret = (string) config('mdm.nano.webhook_secret', '');
        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Hmac-Signature', '');
        if ($signature === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        Cache::put(self::LAST_WEBHOOK_CACHE_KEY, now()->toIso8601String(), now()->addDays(7));

        $topic = (string) (data_get($payload, 'topic') ?? '');

        match ($topic) {
            'mdm.Authenticate' => $this->handleAuthenticate($payload),
            'mdm.TokenUpdate' => $this->handleTokenUpdate($payload),
            'mdm.CheckOut' => $this->handleCheckOut($payload),
            'mdm.Connect' => $this->handleConnect($payload),
            default => $this->handleLegacyCommandStatus($payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleAuthenticate(array $payload): void
    {
        $checkin = data_get($payload, 'checkin_event', []);
        $udid = $this->udidFromCheckin($checkin);
        if (! $udid) {
            Log::warning('NanoMDM Authenticate missing UDID', ['payload' => $payload]);

            return;
        }

        $plist = ApplePlist::decode(data_get($checkin, 'raw_payload'));
        $orgId = $this->resolveOrganizationId($checkin);

        $device = $this->upsertDevice($udid, $orgId, [
            'device_name' => $plist['DeviceName'] ?? null,
            'serial_number' => $plist['SerialNumber'] ?? null,
            'model' => $plist['ProductName'] ?? $plist['ModelName'] ?? null,
            'os_version' => $plist['OSVersion'] ?? null,
            'enrollment_status' => EnrollmentStatus::Enrolled,
            'management_status' => ManagementStatus::Managed,
            'mdm_engine' => MdmEngineType::Nano,
            'is_online' => true,
            'last_contact_at' => now(),
            'raw_info' => array_merge(
                Device::query()->where('udid', $udid)->value('raw_info') ?? [],
                ['authenticate' => $plist ?: $payload]
            ),
        ]);

        $this->recordEvent($device, 'mdm.Authenticate', $payload);
        $this->linkEnrollment($device, $checkin);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleTokenUpdate(array $payload): void
    {
        $checkin = data_get($payload, 'checkin_event', []);
        $udid = $this->udidFromCheckin($checkin);
        if (! $udid) {
            Log::warning('NanoMDM TokenUpdate missing UDID', ['payload' => $payload]);

            return;
        }

        $plist = ApplePlist::decode(data_get($checkin, 'raw_payload'));
        $orgId = $this->resolveOrganizationId($checkin);
        $wasNew = ! Device::query()->where('udid', $udid)->exists();

        $device = $this->upsertDevice($udid, $orgId, [
            'enrollment_status' => EnrollmentStatus::Enrolled,
            'management_status' => ManagementStatus::Managed,
            'mdm_engine' => MdmEngineType::Nano,
            'is_online' => true,
            'last_contact_at' => now(),
        ]);

        $token = $plist['Token'] ?? null;
        if (is_string($token)) {
            $token = trim($token);
        }

        DeviceToken::query()->updateOrCreate(
            ['device_id' => $device->id],
            [
                'push_magic' => $plist['PushMagic'] ?? null,
                'token' => is_string($token) ? $token : null,
                'topic' => $plist['Topic'] ?? config('mdm.nano.topic'),
            ]
        );

        $this->recordEvent($device, 'mdm.TokenUpdate', $payload);
        $this->linkEnrollment($device, $checkin);

        $tally = data_get($checkin, 'token_update_tally');
        if ($wasNew || $tally === 1 || $tally === null) {
            $this->maybeRefreshDevice($device);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckOut(array $payload): void
    {
        $checkin = data_get($payload, 'checkin_event', []);
        $udid = $this->udidFromCheckin($checkin);
        if (! $udid) {
            return;
        }

        $device = Device::query()->where('udid', $udid)->first();
        if (! $device) {
            return;
        }

        $device->fill([
            'enrollment_status' => EnrollmentStatus::Unenrolled,
            'management_status' => ManagementStatus::Unmanaged,
            'is_online' => false,
            'last_contact_at' => now(),
        ])->save();

        DeviceToken::query()->where('device_id', $device->id)->delete();
        $this->recordEvent($device, 'mdm.CheckOut', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleConnect(array $payload): void
    {
        $event = data_get($payload, 'acknowledge_event', []);
        $udid = data_get($event, 'udid') ?: data_get($payload, 'udid');
        $commandUuid = data_get($event, 'command_uuid') ?: data_get($payload, 'command_uuid');
        $status = data_get($event, 'status') ?: data_get($payload, 'status');

        $device = $udid ? Device::query()->where('udid', $udid)->first() : null;
        if ($device) {
            $device->fill([
                'is_online' => true,
                'last_contact_at' => now(),
            ])->save();
        }

        if ($commandUuid) {
            $command = MdmCommand::query()->where('command_uuid', $commandUuid)->first();
            if ($command) {
                $mapped = $this->mapStatus($status);
                $command->fill([
                    'status' => $mapped,
                    'completed_at' => now(),
                    'result' => array_merge($command->result ?? [], ['webhook' => $payload]),
                    'error' => $mapped === MdmCommandStatus::Failed
                        ? (string) (data_get($event, 'error') ?: data_get($payload, 'error') ?: 'Command failed')
                        : $command->error,
                ])->save();

                $command->results()->create([
                    'status' => (string) ($status ?? $mapped->value),
                    'response' => $payload,
                ]);

                $device ??= $command->device;
                if ($device && $command->command_type === MdmCommandType::DeviceInformation) {
                    $this->applyDeviceInformation($device, ApplePlist::decode(data_get($event, 'raw_payload')));
                }
            }
        }

        if ($device) {
            $this->recordEvent($device, 'mdm.Connect', $payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleLegacyCommandStatus(array $payload): void
    {
        $commandUuid = data_get($payload, 'command_uuid')
            ?? data_get($payload, 'event.command_uuid')
            ?? data_get($payload, 'acknowledge_event.command_uuid');

        if (! $commandUuid) {
            Log::info('NanoMDM webhook ignored (unknown topic / no command)', [
                'topic' => data_get($payload, 'topic'),
            ]);

            return;
        }

        $this->handleConnect([
            'topic' => 'mdm.Connect',
            'acknowledge_event' => [
                'command_uuid' => $commandUuid,
                'status' => data_get($payload, 'status') ?? data_get($payload, 'event.status'),
                'udid' => data_get($payload, 'udid') ?? data_get($payload, 'event.udid'),
                'raw_payload' => data_get($payload, 'raw_payload') ?? data_get($payload, 'event.raw_payload'),
            ],
            'legacy' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function upsertDevice(string $udid, int $organizationId, array $attrs): Device
    {
        $device = Device::query()->where('udid', $udid)->first();

        if (! $device) {
            $device = Device::create([
                'organization_id' => $organizationId,
                'udid' => $udid,
                'platform' => 'ios',
                ...array_filter($attrs, fn ($v) => $v !== null),
            ]);

            return $device->fresh();
        }

        $device->fill(array_filter($attrs, fn ($v) => $v !== null))->save();

        return $device->fresh();
    }

    /**
     * @param  array<string, mixed>  $checkin
     */
    protected function resolveOrganizationId(array $checkin): int
    {
        $params = data_get($checkin, 'url_params') ?? data_get($checkin, 'params') ?? [];
        if (is_array($params)) {
            $token = $params['enrollment_token'] ?? $params['token'] ?? null;
            if (is_string($token) && $token !== '') {
                $enrollment = Enrollment::query()->where('token', $token)->first();
                if ($enrollment) {
                    return (int) $enrollment->organization_id;
                }
            }
        }

        $configured = config('mdm.nano.default_organization_id');
        if ($configured) {
            return (int) $configured;
        }

        $first = Organization::query()->orderBy('id')->value('id');
        if ($first) {
            return (int) $first;
        }

        throw new \RuntimeException('No organization available for NanoMDM device upsert. Set NANO_MDM_DEFAULT_ORGANIZATION_ID.');
    }

    /**
     * @param  array<string, mixed>  $checkin
     */
    protected function udidFromCheckin(array $checkin): ?string
    {
        $udid = data_get($checkin, 'udid');
        if (is_string($udid) && $udid !== '') {
            return $udid;
        }

        $plist = ApplePlist::decode(data_get($checkin, 'raw_payload'));
        $fromPlist = $plist['UDID'] ?? null;

        return is_string($fromPlist) && $fromPlist !== '' ? $fromPlist : null;
    }

    /**
     * @param  array<string, mixed>  $checkin
     */
    protected function linkEnrollment(Device $device, array $checkin): void
    {
        $params = data_get($checkin, 'url_params') ?? data_get($checkin, 'params') ?? [];
        if (! is_array($params)) {
            return;
        }

        $token = $params['enrollment_token'] ?? $params['token'] ?? null;
        if (! is_string($token) || $token === '') {
            return;
        }

        $enrollment = Enrollment::query()->where('token', $token)->first();
        if (! $enrollment) {
            return;
        }

        $enrollment->fill([
            'device_id' => $device->id,
            'status' => EnrollmentStatus::Enrolled,
            'completed_at' => $enrollment->completed_at ?? now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function recordEvent(Device $device, string $type, array $payload): void
    {
        DeviceEvent::create([
            'device_id' => $device->id,
            'event_type' => $type,
            'payload' => $payload,
        ]);
    }

    protected function maybeRefreshDevice(Device $device): void
    {
        $recent = MdmCommand::query()
            ->where('device_id', $device->id)
            ->where('command_type', MdmCommandType::DeviceInformation)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($recent) {
            return;
        }

        try {
            $this->commands->dispatch($device, MdmCommandType::DeviceInformation);
        } catch (\Throwable $e) {
            Log::warning('Auto DeviceInformation after TokenUpdate failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $plist
     */
    protected function applyDeviceInformation(Device $device, array $plist): void
    {
        $query = $plist['QueryResponses'] ?? $plist;
        if (! is_array($query)) {
            return;
        }

        $device->fill(array_filter([
            'device_name' => $query['DeviceName'] ?? null,
            'model' => $query['ProductName'] ?? $query['Model'] ?? $query['ModelName'] ?? null,
            'serial_number' => $query['SerialNumber'] ?? null,
            'os_version' => $query['OSVersion'] ?? null,
            'imei' => $query['IMEI'] ?? null,
            'supervised' => array_key_exists('IsSupervised', $query) ? (bool) $query['IsSupervised'] : null,
            'raw_info' => array_merge($device->raw_info ?? [], ['device_information' => $query]),
        ], fn ($v) => $v !== null))->save();
    }

    protected function mapStatus(mixed $status): MdmCommandStatus
    {
        return match (strtolower((string) $status)) {
            'acknowledged', 'success', 'ok', 'idle' => MdmCommandStatus::Acknowledged,
            'notnow' => MdmCommandStatus::DeviceConnected,
            'failed', 'error', 'commandformaterror' => MdmCommandStatus::Failed,
            default => MdmCommandStatus::Executed,
        };
    }
}
