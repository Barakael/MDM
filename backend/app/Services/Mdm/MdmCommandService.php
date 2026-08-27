<?php

namespace App\Services\Mdm;

use App\Enums\MdmCommandStatus;
use App\Enums\MdmCommandType;
use App\Jobs\ProcessMdmCommand;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\MdmCommand;
use App\Models\User;
use Illuminate\Support\Str;

class MdmCommandService
{
    public function __construct(private MdmEngineManager $engines) {}

    public function dispatch(
        Device $device,
        MdmCommandType $type,
        ?User $issuer = null,
        array $payload = []
    ): MdmCommand {
        $engine = $device->resolvedEngine();

        $command = MdmCommand::create([
            'device_id' => $device->id,
            'issued_by' => $issuer?->id,
            'command_uuid' => (string) Str::uuid(),
            'command_type' => $type,
            'engine' => $engine->value,
            'payload' => $payload,
            'status' => MdmCommandStatus::Created,
        ]);

        $command->update([
            'status' => MdmCommandStatus::Queued,
            'queued_at' => now(),
        ]);

        ProcessMdmCommand::dispatch($command->id);

        AuditLog::create([
            'user_id' => $issuer?->id,
            'organization_id' => $device->organization_id,
            'action' => 'mdm.command.queued',
            'auditable_type' => MdmCommand::class,
            'auditable_id' => $command->id,
            'metadata' => [
                'command_type' => $type->value,
                'device_id' => $device->id,
                'engine' => $engine->value,
            ],
            'ip_address' => request()?->ip(),
        ]);

        return $command->fresh();
    }

    public function process(MdmCommand $command): MdmCommand
    {
        $device = $command->device;
        $engine = $this->engines->forDevice($device);

        try {
            $result = match ($command->command_type) {
                MdmCommandType::DeviceInformation => $engine->getDeviceInformation($device),
                MdmCommandType::DeviceLock => $engine->lock($device, $command->payload ?? []),
                MdmCommandType::EnableLostMode => $engine->enableLostMode($device, $command->payload ?? []),
                MdmCommandType::DisableLostMode => $engine->disableLostMode($device),
                MdmCommandType::EraseDevice => $engine->erase($device, $command->payload ?? []),
                MdmCommandType::InstallProfile => $engine->installProfile($device, $command->payload['profile'] ?? ''),
                MdmCommandType::RemoveProfile => $engine->removeProfile($device, $command->payload['identifier'] ?? ''),
            };

            if (! empty($result['command_uuid'])) {
                $command->command_uuid = $result['command_uuid'];
            }

            $command->fill([
                'status' => MdmCommandStatus::Sent,
                'sent_at' => now(),
                'result' => $result,
            ])->save();

            $command->results()->create([
                'status' => 'sent',
                'response' => $result,
            ]);
        } catch (\Throwable $e) {
            $command->fill([
                'status' => MdmCommandStatus::Failed,
                'completed_at' => now(),
                'error' => $e->getMessage(),
            ])->save();

            $command->results()->create([
                'status' => 'failed',
                'response' => ['error' => $e->getMessage()],
            ]);
        }

        return $command->fresh();
    }
}
