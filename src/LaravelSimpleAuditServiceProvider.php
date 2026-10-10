<?php

namespace Danielm\LaravelSimpleAudit;

use Danielm\LaravelSimpleAudit\Console\Commands\ArchiveAuditEvents;
use Illuminate\Support\ServiceProvider;

class LaravelSimpleAuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/simple-audit.php', 'simple-audit');
    }

    public function boot(): void
    {
        // Migrations run automatically on `php artisan migrate` — no
        // publishing required. Publish them only if you need to customize
        // the schema (see README).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/simple-audit.php' => config_path('simple-audit.php'),
            ], 'simple-audit-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'simple-audit-migrations');

            $this->publishes([
                __DIR__.'/../examples' => base_path('simple-audit-examples'),
            ], 'simple-audit-examples');

            $this->commands([
                ArchiveAuditEvents::class,
            ]);
        }
    }
}
