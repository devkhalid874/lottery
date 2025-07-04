<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
      protected $commands = [
        \App\Console\Commands\AutoCloseGames::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // 👇 This line runs your auto-close command every minute
        $schedule->command('auto-close:games')->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
