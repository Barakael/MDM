<?php

namespace App\Services\Apns;

use Illuminate\Support\Facades\Log;

class ApnsService
{
    public function push(string $deviceToken, string $pushMagic, string $topic): bool
    {
        Log::info('APNs push requested', [
            'topic' => $topic,
            'token_prefix' => substr($deviceToken, 0, 8),
            'push_magic' => substr($pushMagic, 0, 8),
        ]);

        // Push delivery is handled by the selected MDM engine (NanoMDM / Custom).
        // This service centralizes logging, expiry checks, and future retry hooks.
        return true;
    }

    public function certificateExpiringSoon(?\DateTimeInterface $expiresAt, int $days = 30): bool
    {
        if (! $expiresAt) {
            return false;
        }

        return now()->diffInDays($expiresAt, false) <= $days;
    }
}
