<?php

namespace App\Services\Mdm;

use App\Enums\MdmEngineType;
use App\Models\Device;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CustomMdmEngine implements MdmEngine
{
    public function getDeviceInformation(Device $device): array
    {
        return $this->send($device, 'DeviceInformation', []);
    }

    public function lock(Device $device, array $data = []): array
    {
        return $this->send($device, 'DeviceLock', $data);
    }

    public function enableLostMode(Device $device, array $data = []): array
    {
        return $this->send($device, 'EnableLostMode', $data);
    }

    public function disableLostMode(Device $device): array
    {
        return $this->send($device, 'DisableLostMode', []);
    }

    public function erase(Device $device, array $data = []): array
    {
        return $this->send($device, 'EraseDevice', $data);
    }

    public function installProfile(Device $device, string $profile): array
    {
        return $this->send($device, 'InstallProfile', ['profile' => $profile]);
    }

    public function removeProfile(Device $device, string $profile): array
    {
        return $this->send($device, 'RemoveProfile', ['identifier' => $profile]);
    }

    protected function send(Device $device, string $requestType, array $payload): array
    {
        if (! $device->udid) {
            throw new RuntimeException('Device UDID is required to send MDM commands.');
        }

        $commandUuid = (string) Str::uuid();

        $response = $this->client()->post('/commands', [
            'udid' => $device->udid,
            'command_uuid' => $commandUuid,
            'request_type' => $requestType,
            'payload' => $payload,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Custom MDM request failed: '.$response->status().' '.$response->body()
            );
        }

        return [
            'engine' => MdmEngineType::Custom->value,
            'command_uuid' => $commandUuid,
            'request_type' => $requestType,
            'response' => $response->json() ?? ['raw' => $response->body()],
        ];
    }

    protected function client(): PendingRequest
    {
        $config = config('mdm.custom');

        $client = Http::baseUrl(rtrim($config['base_url'], '/'))
            ->timeout($config['timeout'] ?? 30)
            ->acceptJson();

        if (! empty($config['api_key'])) {
            $client = $client->withToken($config['api_key']);
        }

        return $client;
    }
}
