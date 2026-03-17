<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Bootstrap;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

final class BootstrapLaravel
{
    public static function bootstrap(string $projectRoot): Application
    {
        $root = realpath($projectRoot);
        if ($root === false) {
            throw new RuntimeException('Unable to resolve project root.');
        }

        $artisan = $root.'/artisan';
        $bootstrap = $root.'/bootstrap/app.php';
        $autoload = $root.'/vendor/autoload.php';

        if (!is_file($artisan) || !is_file($bootstrap) || !is_file($autoload)) {
            throw new RuntimeException('Project root does not look like a Laravel app.');
        }

        chdir($root);

        require_once $autoload;

        /** @var Application $app */
        $app = require $bootstrap;
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}