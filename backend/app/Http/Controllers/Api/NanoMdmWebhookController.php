<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mdm\NanoMdmWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NanoMdmWebhookController extends Controller
{
    public function __invoke(Request $request, NanoMdmWebhookService $webhooks): JsonResponse
    {
        if (! $webhooks->verifyHmac($request)) {
            Log::warning('NanoMDM webhook HMAC verification failed');

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        $payload = $request->all();
        Log::info('NanoMDM webhook', [
            'topic' => data_get($payload, 'topic'),
            'udid' => data_get($payload, 'checkin_event.udid') ?? data_get($payload, 'acknowledge_event.udid'),
        ]);

        try {
            $webhooks->handle($payload);
        } catch (\Throwable $e) {
            Log::error('NanoMDM webhook handling failed', [
                'error' => $e->getMessage(),
                'topic' => data_get($payload, 'topic'),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true]);
    }
}
