<?php

namespace Juice\Backups\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

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
        if (!is_dir(config('juice-backups.destination'))) {
            $this->warn('Backup directory does not exist.');
            return;
        }

        $backups = $this->getBackups();

        if ($backups->isEmpty()) {
            $this->info('No backups need to be cleanup.');
            return;
        }

        [$beforeLastMonth, $inThePastMonth] = $backups->groupBy('in-month');

        // backups that before last month will only preserve every 7 days
        if ($beforeLastMonth->count() > 1) {
            $preview = Carbon::createFromFormat('Y-m-d', $beforeLastMonth->shift()['date'])->startOfDay();

            foreach ($beforeLastMonth as $backup) {
                if ($preview->diffInDays($backup['date']) < 7) {
                    unlink($backup['path']);
                } else {
                    $preview->setDate(...explode('-', $backup['date']));
                }
            }
        }

        // in the past month backups will only preserve one backup per day
        if ($inThePastMonth->count() > 1) {
            $exists = [];

            foreach ($inThePastMonth as $backup) {
                if (isset($exists[$backup['date']])) {
                    unlink($backup['path']);
                } else {
                    $exists[$backup['date']] = true;
                }
            }
        }

        $this->info('Backup cleanup successfully.');
    }

    protected function getBackups()
    {
        $finder = (new Finder)
            ->files()
            ->depth(0)
            ->name(sprintf('%s-*', rtrim(config('juice-backups.name'), '-')))
            ->in(config('juice-backups.destination'));

        $backups = collect();

        foreach ($finder->getIterator() as $file) {
            $time = Carbon::createFromFormat(
                'Y-m-d-H-i-s',
                substr(strstr($file->getFilename(), '.', true), 6)
            );

            if ($time->diffInHours() < 24) {
                continue;
            }

            $backups->push([
                'date' => $time->toDateString(),
                'in-month' => $time->diffInDays() <= 31,
                'path' => $file->getPathname(),
                'timestamp' => $time->timestamp,
            ]);
        }

        return $backups->sortBy('timestamp')->values();
    }
}
