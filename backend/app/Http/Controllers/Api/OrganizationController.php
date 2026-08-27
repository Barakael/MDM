<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Organization::query()->withCount('devices')->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $query->where('id', $user->organization_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:organizations,slug'],
            'is_active' => ['boolean'],
        ]);

        $org = Organization::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $org->id,
            'action' => 'organization.created',
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['data' => $org], 201);
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($request->user()->canAccessOrganization($organization->id), 403);

        $organization->loadCount('devices')->load(['users.roles']);

        return response()->json(['data' => $organization]);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:organizations,slug,'.$organization->id],
            'is_active' => ['boolean'],
        ]);

        $organization->update($data);

        return response()->json(['data' => $organization]);
    }
}
