<?php

namespace App\Services\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentProfile;
use App\Models\Organization;
use Illuminate\Support\Str;

class EnrollmentService
{
    public function createConfiguratorEnrollment(Organization $organization, ?int $daysValid = 7): Enrollment
    {
        $enrollment = Enrollment::create([
            'organization_id' => $organization->id,
            'token' => Str::random(40),
            'method' => 'configurator',
            'status' => EnrollmentStatus::Pending,
            'expires_at' => now()->addDays($daysValid),
        ]);

        $url = url('/mdm/enroll/'.$enrollment->token);

        EnrollmentProfile::create([
            'enrollment_id' => $enrollment->id,
            'profile_uuid' => (string) Str::uuid(),
            'payload' => $this->buildPlaceholderProfile($organization, $url),
        ]);

        return $enrollment->load('profile');
    }

    public function enrollmentUrl(Enrollment $enrollment): string
    {
        return url('/mdm/enroll/'.$enrollment->token);
    }

    protected function buildPlaceholderProfile(Organization $organization, string $enrollmentUrl): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>PayloadDisplayName</key>
    <string>{$organization->name} MDM Enrollment</string>
    <key>PayloadIdentifier</key>
    <string>com.mdm.platform.enrollment.{$organization->slug}</string>
    <key>PayloadType</key>
    <string>Configuration</string>
    <key>PayloadUUID</key>
    <string>{$this->uuid()}</string>
    <key>PayloadVersion</key>
    <integer>1</integer>
    <key>EnrollmentURL</key>
    <string>{$enrollmentUrl}</string>
</dict>
</plist>
XML;
    }

    protected function uuid(): string
    {
        return (string) Str::uuid();
    }
}
