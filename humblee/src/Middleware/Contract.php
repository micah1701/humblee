<?php

declare(strict_types=1);

namespace Humblee\Middleware;

interface Contract
{
    public function handle(Package $package): void;
}
