<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MdmCommand;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isAdmin(), 403);

        $query = User::query()->with(['roles', 'organization'])->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'role' => ['required', Rule::in(['Super_Admin', 'Admin'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $data['organization_id'] ?? null,
            'is_active' => true,
        ]);

        $role = \App\Models\Role::where('name', $data['role'])->firstOrFail();
        $user->roles()->attach($role);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $user->organization_id,
            'action' => 'user.created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['data' => $user->load('roles')], 201);
    }
}
