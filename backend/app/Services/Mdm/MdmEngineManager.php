<?php

namespace App\Services\Mdm;

use App\Enums\MdmEngineType;
use App\Models\Device;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class MdmEngineManager
{
    public function __construct(private Container $app) {}

    public function forDevice(Device $device): MdmEngine
    {
        return $this->resolve($device->resolvedEngine());
    }

    public function resolve(MdmEngineType|string $engine): MdmEngine
    {
        $type = $engine instanceof MdmEngineType
            ? $engine
            : MdmEngineType::from($engine);

        return match ($type) {
            MdmEngineType::Nano => $this->app->make(NanoMdmEngine::class),
            MdmEngineType::Custom => $this->app->make(CustomMdmEngine::class),
            default => throw new RuntimeException('Invalid MDM engine'),
        };
    }
}
