<?php

namespace Juice\Backups\Commands;

use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup outdated backups.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        //
    }
}
