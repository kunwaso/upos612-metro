<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->suppressPhp84DeprecationNoise();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $this->suppressPhp84DeprecationNoise();

        return $app;
    }

    /**
     * Keep local test output readable on PHP 8.4+ where vendor deprecations
     * can drown actual test failures. This is test-runtime only.
     */
    protected function suppressPhp84DeprecationNoise(): void
    {
        error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }
}
