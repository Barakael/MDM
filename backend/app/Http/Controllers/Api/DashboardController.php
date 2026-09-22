<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();
        $deviceQuery = \App\Models\Device::query();
        $commandQuery = \App\Models\MdmCommand::query();
        $orgQuery = \App\Models\Organization::query();

        if (! $user->isSuperAdmin()) {
            $deviceQuery->where('organization_id', $user->organization_id);
            $commandQuery->whereHas('device', fn ($q) => $q->where('organization_id', $user->organization_id));
            $orgQuery->where('id', $user->organization_id);
        }

        return response()->json([
            'data' => [
                'organizations' => $orgQuery->count(),
                'devices' => $deviceQuery->count(),
                'online_devices' => (clone $deviceQuery)->where('is_online', true)->count(),
                'pending_commands' => (clone $commandQuery)->whereIn('status', ['created', 'queued', 'sent'])->count(),
                'mdm_engine' => config('mdm.engine'),
                'mdm_reachable' => null,
            ],
        ]);
    }
}
