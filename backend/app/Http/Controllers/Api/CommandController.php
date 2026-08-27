<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MdmCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = MdmCommand::query()
            ->with(['device:id,device_name,udid,organization_id', 'issuer:id,name'])
            ->latest();

        if (! $user->isSuperAdmin()) {
            $query->whereHas('device', fn ($q) => $q->where('organization_id', $user->organization_id));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function show(Request $request, MdmCommand $command): JsonResponse
    {
        $command->load(['device', 'issuer', 'results']);
        abort_unless($request->user()->canAccessOrganization($command->device->organization_id), 403);

        return response()->json(['data' => $command]);
    }
}
