<?php

namespace App\Services\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentProfile;
use App\Models\Organization;
use Illuminate\Support\Str;
use RuntimeException;

class EnrollmentService
{
    public function createConfiguratorEnrollment(Organization $organization, ?int $daysValid = 7): Enrollment
    {
        $this->assertNanoProfileConfig();

        $enrollment = Enrollment::create([
            'organization_id' => $organization->id,
            'token' => Str::random(40),
            'method' => 'configurator',
            'status' => EnrollmentStatus::Pending,
            'expires_at' => now()->addDays($daysValid),
        ]);

        EnrollmentProfile::create([
            'enrollment_id' => $enrollment->id,
            'profile_uuid' => (string) Str::uuid(),
            'payload' => $this->buildNanoEnrollmentProfile($organization, $enrollment),
        ]);

        return $enrollment->load('profile');
    }

    public function enrollmentUrl(Enrollment $enrollment): string
    {
        return rtrim((string) config('app.url'), '/').'/mdm/enroll/'.$enrollment->token;
    }

    public function profileEndpoints(Enrollment $enrollment): array
    {
        $public = rtrim((string) config('mdm.nano.public_url'), '/');
        $serverUrl = $public.'/mdm?enrollment_token='.urlencode($enrollment->token);

        return [
            'app_url' => (string) config('app.url'),
            'server_url' => $serverUrl,
            'check_in_url' => $serverUrl,
            'scep_url' => (string) config('mdm.nano.scep_url'),
            'topic' => (string) config('mdm.nano.topic'),
        ];
    }

    protected function assertNanoProfileConfig(): void
    {
        if (! filled(config('mdm.nano.public_url'))) {
            throw new RuntimeException('NANO_MDM_PUBLIC_URL is required to build enrollment profiles.');
        }
        if (! filled(config('mdm.nano.scep_url'))) {
            throw new RuntimeException('NANO_SCEP_URL is required to build enrollment profiles.');
        }
        if (! filled(config('mdm.nano.topic'))) {
            throw new RuntimeException('NANO_MDM_TOPIC is required to build enrollment profiles (from pushcert upload).');
        }
    }

    protected function buildNanoEnrollmentProfile(Organization $organization, Enrollment $enrollment): string
    {
        $endpoints = $this->profileEndpoints($enrollment);
        $scepUrl = $this->xml($endpoints['scep_url']);
        $challenge = $this->xml((string) config('mdm.nano.scep_challenge', 'nanomdm'));
        $serverUrl = $this->xml($endpoints['server_url']);
        $topic = $this->xml($endpoints['topic']);
        $displayName = $this->xml($organization->name.' MDM Enrollment');
        $orgSlug = $this->xml($organization->slug ?: 'org');
        $scepUuid = strtoupper((string) Str::uuid());
        $mdmUuid = strtoupper((string) Str::uuid());
        $payloadUuid = strtoupper((string) Str::uuid());

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>PayloadContent</key>
	<array>
		<dict>
			<key>PayloadContent</key>
			<dict>
				<key>Key Type</key>
				<string>RSA</string>
				<key>Challenge</key>
				<string>{$challenge}</string>
				<key>Key Usage</key>
				<integer>5</integer>
				<key>Keysize</key>
				<integer>2048</integer>
				<key>URL</key>
				<string>{$scepUrl}</string>
			</dict>
			<key>PayloadIdentifier</key>
			<string>com.mdm.platform.scep.{$orgSlug}</string>
			<key>PayloadType</key>
			<string>com.apple.security.scep</string>
			<key>PayloadUUID</key>
			<string>{$scepUuid}</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
		<dict>
			<key>AccessRights</key>
			<integer>8191</integer>
			<key>CheckOutWhenRemoved</key>
			<true/>
			<key>IdentityCertificateUUID</key>
			<string>{$scepUuid}</string>
			<key>PayloadIdentifier</key>
			<string>com.mdm.platform.mdm.{$orgSlug}</string>
			<key>PayloadType</key>
			<string>com.apple.mdm</string>
			<key>PayloadUUID</key>
			<string>{$mdmUuid}</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
			<key>ServerCapabilities</key>
			<array>
				<string>com.apple.mdm.per-user-connections</string>
				<string>com.apple.mdm.bootstraptoken</string>
				<string>com.apple.mdm.token</string>
			</array>
			<key>ServerURL</key>
			<string>{$serverUrl}</string>
			<key>CheckInURL</key>
			<string>{$serverUrl}</string>
			<key>SignMessage</key>
			<true/>
			<key>Topic</key>
			<string>{$topic}</string>
		</dict>
	</array>
	<key>PayloadDisplayName</key>
	<string>{$displayName}</string>
	<key>PayloadIdentifier</key>
	<string>com.mdm.platform.enrollment.{$orgSlug}</string>
	<key>PayloadRemovalDisallowed</key>
	<false/>
	<key>PayloadType</key>
	<string>Configuration</string>
	<key>PayloadUUID</key>
	<string>{$payloadUuid}</string>
	<key>PayloadVersion</key>
	<integer>1</integer>
</dict>
</plist>
XML;
    }

    protected function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
