<?php

namespace App\Services\Mdm;

use App\Enums\MdmEngineType;
use App\Models\Device;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NanoMdmEngine implements MdmEngine
{
    public function getDeviceInformation(Device $device): array
    {
        return $this->enqueue($device, [
            'RequestType' => 'DeviceInformation',
            'Queries' => [
                'DeviceName',
                'OSVersion',
                'ModelName',
                'SerialNumber',
                'UDID',
                'IsSupervised',
            ],
        ]);
    }

    public function lock(Device $device, array $data = []): array
    {
        return $this->enqueue($device, array_filter([
            'RequestType' => 'DeviceLock',
            'PIN' => $data['pin'] ?? null,
            'Message' => $data['message'] ?? null,
            'PhoneNumber' => $data['phone_number'] ?? null,
        ]));
    }

    public function enableLostMode(Device $device, array $data = []): array
    {
        return $this->enqueue($device, [
            'RequestType' => 'EnableLostMode',
            'Message' => $data['message'] ?? 'This device has been lost. Please call.',
            'PhoneNumber' => $data['phone_number'] ?? '',
            'Footnote' => $data['footnote'] ?? '',
        ]);
    }

    public function disableLostMode(Device $device): array
    {
        return $this->enqueue($device, ['RequestType' => 'DisableLostMode']);
    }

    public function erase(Device $device, array $data = []): array
    {
        return $this->enqueue($device, array_filter([
            'RequestType' => 'EraseDevice',
            'PIN' => $data['pin'] ?? null,
        ]));
    }

    public function installProfile(Device $device, string $profile): array
    {
        return $this->enqueue($device, [
            'RequestType' => 'InstallProfile',
            'Payload' => $profile,
        ]);
    }

    public function removeProfile(Device $device, string $profile): array
    {
        return $this->enqueue($device, [
            'RequestType' => 'RemoveProfile',
            'Identifier' => $profile,
        ]);
    }

    protected function enqueue(Device $device, array $command): array
    {
        if (! $device->udid) {
            throw new RuntimeException('Device UDID is required to send MDM commands.');
        }

        $commandUuid = (string) Str::uuid();
        $plist = NanoMdmPlistBuilder::command($commandUuid, $command);

        $response = $this->client()
            ->withBody($plist, 'application/x-apple-aspen-config')
            ->put("/v1/enqueue/{$device->udid}", []);

        if ($response->failed()) {
            throw new RuntimeException(
                'NanoMDM request failed: '.$response->status().' '.$response->body()
            );
        }

        $json = $response->json() ?? [];

        return [
            'engine' => MdmEngineType::Nano->value,
            'command_uuid' => $json['command_uuid'] ?? $commandUuid,
            'request_type' => $json['request_type'] ?? ($command['RequestType'] ?? null),
            'response' => $json ?: ['raw' => $response->body()],
        ];
    }

    protected function client(): PendingRequest
    {
        $config = config('mdm.nano');

        $client = Http::baseUrl(rtrim($config['base_url'], '/'))
            ->timeout($config['timeout'] ?? 30)
            ->acceptJson();

        $apiKey = $config['api_key'] ?? '';
        if ($apiKey !== '') {
            // NanoMDM API auth: HTTP Basic, username is always "nanomdm".
            $client = $client->withBasicAuth('nanomdm', $apiKey);
        }

        return $client;
    }
}
