<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\StoreAgvData;

class Kernel extends ConsoleKernel
{
    protected $middleware = [
        // ...
        \Barryvdh\Cors\HandleCors::class,
    ];
    protected $commands = [
        \App\Console\Commands\FetchAGVData::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('agv:websocket-client')->everyMinute();
        //$schedule->command(StoreAgvData::class)->everySecond();
        $schedule->command('websocket:listen')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
