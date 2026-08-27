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
        return $this->enqueue($device, 'DeviceInformation', [
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
        return $this->enqueue($device, 'DeviceLock', array_filter([
            'PIN' => $data['pin'] ?? null,
            'Message' => $data['message'] ?? null,
            'PhoneNumber' => $data['phone_number'] ?? null,
        ]));
    }

    public function enableLostMode(Device $device, array $data = []): array
    {
        return $this->enqueue($device, 'EnableLostMode', [
            'Message' => $data['message'] ?? 'This device has been lost. Please call.',
            'PhoneNumber' => $data['phone_number'] ?? '',
            'Footnote' => $data['footnote'] ?? '',
        ]);
    }

    public function disableLostMode(Device $device): array
    {
        return $this->enqueue($device, 'DisableLostMode', []);
    }

    public function erase(Device $device, array $data = []): array
    {
        return $this->enqueue($device, 'EraseDevice', array_filter([
            'PIN' => $data['pin'] ?? null,
        ]));
    }

    public function installProfile(Device $device, string $profile): array
    {
        return $this->enqueue($device, 'InstallProfile', [
            'Payload' => base64_encode($profile),
        ]);
    }

    public function removeProfile(Device $device, string $profile): array
    {
        return $this->enqueue($device, 'RemoveProfile', [
            'Identifier' => $profile,
        ]);
    }

    protected function enqueue(Device $device, string $requestType, array $payload): array
    {
        if (! $device->udid) {
            throw new RuntimeException('Device UDID is required to send MDM commands.');
        }

        $commandUuid = (string) Str::uuid();
        $body = array_merge(['RequestType' => $requestType, 'CommandUUID' => $commandUuid], $payload);

        $response = $this->client()
            ->put("/v1/commands/{$device->udid}", $body);

        if ($response->failed()) {
            throw new RuntimeException(
                'NanoMDM request failed: '.$response->status().' '.$response->body()
            );
        }

        return [
            'engine' => MdmEngineType::Nano->value,
            'command_uuid' => $commandUuid,
            'request_type' => $requestType,
            'response' => $response->json() ?? ['raw' => $response->body()],
        ];
    }

    protected function client(): PendingRequest
    {
        $config = config('mdm.nano');

        $client = Http::baseUrl(rtrim($config['base_url'], '/'))
            ->timeout($config['timeout'] ?? 30)
            ->acceptJson();

        if (! empty($config['api_key'])) {
            $client = $client->withToken($config['api_key']);
        }

        return $client;
    }
}
