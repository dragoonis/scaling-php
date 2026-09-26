<?php

namespace App\Metrics;

use Beberlei\Metrics\Collector\CollectorInterface;
use Illuminate\Support\Facades\Facade;

class Metrics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CollectorInterface::class;
    }
}
