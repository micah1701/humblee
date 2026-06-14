<?php

declare(strict_types=1);

namespace Humblee\Middleware;

class Kernel
{
    private static array $core = [
        Auth::class,
    ];

    public static function boot(): void
    {
        $package = Package::build();

        foreach (self::$core as $class) {
            (new $class())->handle($package);
        }

        self::loadAppMiddleware($package);

        (new Router())->handle($package);
    }

    private static function loadAppMiddleware(Package $package): void
    {
        $dir = _app_server_path . '/application/middleware';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.php') as $file) {
            require_once $file;
            $class = 'App\\Middleware\\' . basename($file, '.php');
            if (class_exists($class) && is_a($class, Contract::class, true)) {
                (new $class())->handle($package);
            }
        }
    }
}
