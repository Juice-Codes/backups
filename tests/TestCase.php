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
}
