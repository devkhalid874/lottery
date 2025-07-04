<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Game;
use Illuminate\Console\Command;

class AutoCloseGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-close:games';
    protected $description = 'Auto-close games whose close_time has passed';

    /**
     * The console command description.
     *
     * @var string
     */
  

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $games = Game::where('auto_close', 1)
            ->where('close_time', '<=', Carbon::now())
            ->get();

        foreach ($games as $game) {
            $game->status = 0;
            $game->save();
        }

        $this->info('Auto-closed expired games.');
    }
}
