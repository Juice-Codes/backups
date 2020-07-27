<?php

namespace Juice\Backups;

use Illuminate\Support\ServiceProvider;
use Juice\Backups\Commands\CleanupCommand;
use Juice\Backups\Commands\RunCommand;
use Juice\Backups\Commands\SetupCommand;
use Laravel\Lumen\Application;

class BackupsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app instanceof Application) {
            $this->app->configure('juice-backups');
        } else {
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__ . '/../config/juice-backups.php' => config_path('juice-backups.php'),
                ], 'config');
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupCommand::class,
                RunCommand::class,
                SetupCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/juice-backups.php', 'juice-backups'
        );
    }
}
