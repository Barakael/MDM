<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mdm\NanoMdmWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MdmStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $engine = (string) config('mdm.engine', 'nano');
        $baseUrl = rtrim((string) config('mdm.nano.base_url'), '/');
        $apiKey = (string) config('mdm.nano.api_key');
        $timeout = (int) config('mdm.nano.timeout', 30);

        $reachable = false;
        $version = null;
        $error = null;

        if ($engine === 'nano' && $baseUrl !== '') {
            try {
                $response = Http::timeout(min($timeout, 5))
                    ->withBasicAuth('nanomdm', $apiKey)
                    ->acceptJson()
                    ->get($baseUrl.'/version');

                $reachable = $response->successful();
                $version = $response->json() ?? $response->body();
                if (! $reachable) {
                    $error = 'HTTP '.$response->status();
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return response()->json([
            'data' => [
                'engine' => $engine,
                'reachable' => $reachable,
                'version' => $version,
                'error' => $error,
                'last_webhook_at' => Cache::get(NanoMdmWebhookService::LAST_WEBHOOK_CACHE_KEY),
                'urls' => [
                    'app_url' => (string) config('app.url'),
                    'base_url' => (string) config('mdm.nano.base_url'),
                    'public_url' => (string) config('mdm.nano.public_url'),
                    'scep_url' => (string) config('mdm.nano.scep_url'),
                    'webhook_url' => (string) config('mdm.nano.webhook_url'),
                    'topic' => (string) config('mdm.nano.topic'),
                    'topic_configured' => filled(config('mdm.nano.topic')),
                    'scep_challenge_configured' => filled(config('mdm.nano.scep_challenge')),
                    'webhook_secret_configured' => filled(config('mdm.nano.webhook_secret')),
                    'default_organization_id' => config('mdm.nano.default_organization_id'),
                ],
            ],
        ]);
    }
}
