<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MdmGatewayController extends Controller
{
    /**
     * Apple-facing enrollment profile download (Configurator).
     */
    public function enroll(string $token): Response
    {
        $enrollment = Enrollment::query()
            ->with('profile')
            ->where('token', $token)
            ->firstOrFail();

        abort_if(
            $enrollment->expires_at && $enrollment->expires_at->isPast(),
            410,
            'Enrollment expired'
        );

        $enrollment->update(['status' => EnrollmentStatus::Enrolling]);

        $payload = $enrollment->profile?->payload ?? '';

        return response($payload, 200, [
            'Content-Type' => 'application/x-apple-aspen-config',
            'Content-Disposition' => 'attachment; filename="enrollment.mobileconfig"',
        ]);
    }

    /**
     * MDM CheckIn — Authenticate / TokenUpdate / CheckOut (Apple protocol).
     * Full plist parsing is deferred to Custom MDM; this records contact for Nano path.
     */
    public function checkin(Request $request): Response
    {
        \Log::info('MDM checkin', ['content_type' => $request->header('Content-Type')]);

        return response('', 200);
    }

    /**
     * MDM Server URL connect / idle / command response.
     */
    public function connect(Request $request): Response
    {
        \Log::info('MDM connect', ['bytes' => strlen($request->getContent())]);

        return response('', 200);
    }
}
