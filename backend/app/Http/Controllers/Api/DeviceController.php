<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\ManagementStatus;
use App\Enums\MdmCommandType;
use App\Enums\MdmEngineType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Device;
use App\Services\Mdm\MdmCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Device::query()->with('organization')->orderByDesc('updated_at');

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($request->filled('organization_id')) {
            $query->where('organization_id', $request->integer('organization_id'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('device_name', 'like', $q)
                    ->orWhere('serial_number', 'like', $q)
                    ->orWhere('udid', 'like', $q);
            });
        }

        return response()->json(['data' => $query->paginate(25)]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'udid' => ['nullable', 'string', 'max:255', 'unique:devices,udid'],
            'imei' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:50'],
            'supervised' => ['boolean'],
            'mdm_engine' => ['nullable', Rule::enum(MdmEngineType::class)],
        ]);

        abort_unless($user->canAccessOrganization((int) $data['organization_id']), 403);

        $device = Device::create([
            ...$data,
            'management_status' => ManagementStatus::Unmanaged,
            'enrollment_status' => EnrollmentStatus::Pending,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $device->organization_id,
            'action' => 'device.created',
            'auditable_type' => Device::class,
            'auditable_id' => $device->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['data' => $device], 201);
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        abort_unless($request->user()->canAccessOrganization($device->organization_id), 403);

        $device->load([
            'organization',
            'commands' => fn ($q) => $q->latest()->limit(50),
            'applications',
            'profiles',
            'certificates',
            'events' => fn ($q) => $q->latest()->limit(50),
        ]);

        return response()->json([
            'data' => $device,
            'resolved_engine' => $device->resolvedEngine()->value,
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        abort_unless($request->user()->canAccessOrganization($device->organization_id), 403);

        $data = $request->validate([
            'device_name' => ['sometimes', 'string', 'max:255'],
            'mdm_engine' => ['nullable', Rule::enum(MdmEngineType::class)],
            'supervised' => ['boolean'],
        ]);

        $device->update($data);

        return response()->json([
            'data' => $device->fresh(),
            'resolved_engine' => $device->fresh()->resolvedEngine()->value,
        ]);
    }

    public function refresh(Request $request, Device $device, MdmCommandService $commands): JsonResponse
    {
        return $this->command($request, $device, $commands, MdmCommandType::DeviceInformation);
    }

    public function lock(Request $request, Device $device, MdmCommandService $commands): JsonResponse
    {
        $payload = $request->validate([
            'pin' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->command($request, $device, $commands, MdmCommandType::DeviceLock, $payload);
    }

    public function enableLostMode(Request $request, Device $device, MdmCommandService $commands): JsonResponse
    {
        $payload = $request->validate([
            'message' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'footnote' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->command($request, $device, $commands, MdmCommandType::EnableLostMode, $payload);
    }

    public function disableLostMode(Request $request, Device $device, MdmCommandService $commands): JsonResponse
    {
        return $this->command($request, $device, $commands, MdmCommandType::DisableLostMode);
    }

    public function erase(Request $request, Device $device, MdmCommandService $commands): JsonResponse
    {
        $payload = $request->validate([
            'pin' => ['nullable', 'string', 'max:20'],
            'confirm' => ['required', 'accepted'],
        ]);

        unset($payload['confirm']);

        return $this->command($request, $device, $commands, MdmCommandType::EraseDevice, $payload);
    }

    public function commands(Request $request, Device $device): JsonResponse
    {
        abort_unless($request->user()->canAccessOrganization($device->organization_id), 403);

        return response()->json([
            'data' => $device->commands()->with('issuer:id,name,email')->latest()->paginate(50),
        ]);
    }

    protected function command(
        Request $request,
        Device $device,
        MdmCommandService $commands,
        MdmCommandType $type,
        array $payload = []
    ): JsonResponse {
        abort_unless($request->user()->canAccessOrganization($device->organization_id), 403);

        $command = $commands->dispatch($device, $type, $request->user(), $payload);

        return response()->json(['data' => $command], 202);
    }
}
