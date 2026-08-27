<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MdmCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AuditLog::query()->with('user:id,name,email')->latest();

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        return response()->json(['data' => $query->paginate(50)]);
    }
}
