<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole() || $this->app->environment('testing')) {
            return;
        }

        $command = $_SERVER['argv'][1] ?? null;
        $dangerous = ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'];

        if (! in_array($command, $dangerous, true)) {
            return;
        }

        if (filter_var(env('ALLOW_DESTRUCTIVE_DB', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        fwrite(STDERR, "Blocked `{$command}` on [{$connection}] database \"{$database}\".\n");
        fwrite(STDERR, "That command deletes all tables/data.\n");
        fwrite(STDERR, "Use: php artisan migrate\n");
        fwrite(STDERR, "Only if you really want a wipe, set ALLOW_DESTRUCTIVE_DB=true in .env.\n");

        exit(1);
    }
}
