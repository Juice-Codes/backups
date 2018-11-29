<?php

namespace Juice\Tests;

use Illuminate\Support\Facades\File;
use Juice\Backups\BackupsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {
        File::deleteDirectory(storage_path('jb-backups'));
        File::delete(File::files(__DIR__.'/temp'));

        parent::tearDown();
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [BackupsServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'username' => 'testing',
            'password' => 'testing',
            'prefix'   => '',
        ]);
    }
}
