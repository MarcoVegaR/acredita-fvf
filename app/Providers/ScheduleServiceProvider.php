<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Limpiar archivos huérfanos de imágenes y documentos cada semana (domingo a las 2 AM)
            $schedule->command('app:clean-orphaned-files')
                     ->weekly()
                     ->sundays()
                     ->at('02:00')
                     ->appendOutputTo(storage_path('logs/orphaned-files-cleanup.log'))
                     ->emailOutputOnFailure(config('mail.admin_address'));
        });
    }
}
