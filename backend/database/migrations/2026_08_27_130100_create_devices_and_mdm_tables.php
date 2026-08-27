<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('platform')->default('ios');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->string('udid')->nullable()->unique();
            $table->string('imei')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('supervised')->default(false);
            $table->string('management_status')->default('unmanaged');
            $table->string('enrollment_status')->default('pending');
            $table->string('mdm_engine')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_contact_at')->nullable();
            $table->json('raw_info')->nullable();
            $table->timestamps();
        });

        Schema::create('device_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->timestamps();
            $table->unique(['device_id', 'type']);
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->text('push_magic')->nullable();
            $table->text('token')->nullable();
            $table->string('topic')->nullable();
            $table->timestamps();
        });

        Schema::create('device_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('common_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('fingerprint')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('method')->default('configurator');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('profile_uuid')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('mdm_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('command_uuid')->unique();
            $table->string('command_type');
            $table->string('engine');
            $table->json('payload')->nullable();
            $table->string('status')->default('created');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
            $table->index(['device_id', 'status']);
        });

        Schema::create('mdm_command_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mdm_command_id')->constrained()->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });

        Schema::create('configuration_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('device_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('configuration_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('profile_identifier')->nullable();
            $table->string('display_name')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('installed_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('bundle_identifier')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();
        });

        Schema::create('device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('apns_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mdm_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('engine')->default('nano');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdm_credentials');
        Schema::dropIfExists('apns_credentials');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('device_events');
        Schema::dropIfExists('installed_applications');
        Schema::dropIfExists('device_profiles');
        Schema::dropIfExists('configuration_profiles');
        Schema::dropIfExists('mdm_command_results');
        Schema::dropIfExists('mdm_commands');
        Schema::dropIfExists('enrollment_profiles');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('device_certificates');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('device_identifiers');
        Schema::dropIfExists('devices');
    }
};
