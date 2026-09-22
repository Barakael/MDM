<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Enrollment::query()->with(['organization', 'device', 'profile'])->latest();

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        return response()->json(['data' => $query->paginate(25)]);
    }

    public function store(Request $request, EnrollmentService $enrollments): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'method' => ['nullable', 'in:configurator'],
        ]);

        abort_unless($user->canAccessOrganization((int) $data['organization_id']), 403);

        $org = \App\Models\Organization::findOrFail($data['organization_id']);

        try {
            $enrollment = $enrollments->createConfiguratorEnrollment($org);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'action' => 'enrollment.created',
            'auditable_type' => Enrollment::class,
            'auditable_id' => $enrollment->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => $enrollment,
            'enrollment_url' => $enrollments->enrollmentUrl($enrollment),
            'profile_endpoints' => $enrollments->profileEndpoints($enrollment),
        ], 201);
    }

    public function show(Request $request, Enrollment $enrollment, EnrollmentService $enrollments): JsonResponse
    {
        abort_unless($request->user()->canAccessOrganization($enrollment->organization_id), 403);

        $enrollment->load(['organization', 'device', 'profile']);

        return response()->json([
            'data' => $enrollment,
            'enrollment_url' => $enrollments->enrollmentUrl($enrollment),
            'profile_endpoints' => $enrollments->profileEndpoints($enrollment),
        ]);
    }
}
