<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAuthentication;

    /**
     * @return Application
     */
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->configureTestingDatabase($app);

        return $app;
    }

    protected function configureTestingDatabase(Application $app): void
    {
        $memorySqlite = [
            'driver' => 'sqlite',
            'url' => null,
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        // Default connection for tenant-domain models in tests.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', $memorySqlite);

        // Landlord migrations (tenants, domains, platform_admins) declare $connection = 'central'.
        // Without this override they keep the production pgsql driver and fail with database ":memory:".
        $app['config']->set('database.connections.central', $memorySqlite);

        $app['config']->set('tenancy.database.central_connection', 'sqlite');
    }
}
