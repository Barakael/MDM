<?php

namespace App\Providers;

use App\Enums\MdmEngineType;
use App\Services\Mdm\CustomMdmEngine;
use App\Services\Mdm\MdmEngine;
use App\Services\Mdm\MdmEngineManager;
use App\Services\Mdm\NanoMdmEngine;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MdmEngineManager::class);

        $this->app->bind(MdmEngine::class, function ($app) {
            $engine = config('mdm.engine', 'nano');

            return match ($engine) {
                MdmEngineType::Nano->value, 'nano' => $app->make(NanoMdmEngine::class),
                MdmEngineType::Custom->value, 'custom' => $app->make(CustomMdmEngine::class),
                default => throw new RuntimeException('Invalid MDM engine'),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
