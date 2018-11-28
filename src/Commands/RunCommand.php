<?php

namespace Juice\Backups\Commands;

use Carbon\Carbon;
use Generator;
use Illuminate\Console\Command;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\DbDumper;
use Symfony\Component\Finder\Finder;
use wapmorgan\UnifiedArchive\TarArchive;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database and application backup.';

    /**
     * Juice backups config.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->config = config('juice-backups');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $paths = [];

        foreach ($this->getIncludes() as $directory) {
            $finder = (new Finder)
                ->ignoreDotFiles(false)
                ->in($directory)
                ->exclude($this->getExcludes($directory));

            foreach ($finder->getIterator() as $file) {
                $paths[] = $file->getPathname();
            }
        }

        $files = array_filter($this->config['includes'], 'is_file');

        if (!empty($files)) {
            array_push($paths, ...$files);
        }

        $paths = array_map(function ($path) {
            return str_replace(realpath(base_path('../')), '.', $path);
        }, array_values(array_diff(
            array_unique($paths),
            array_filter($this->config['excludes'], 'is_file')
        )));

        if (!is_dir($this->config['destination'])) {
            mkdir($this->config['destination'], 0777, true);
        }

        $progress = $this->output->createProgressBar(count($paths) + 1);

        $progress->start();

        chdir('../');

        $archive = new TarArchive(sprintf(
            '%s/%s-%s.tar.gz',
            $this->config['destination'],
            trim($this->config['name'], '-'),
            Carbon::now()->format('Y-m-d-H-i-s')
        ), 'tgz');

        foreach ($paths as $path) {
            is_dir($path) ? $archive->addDirectory($path) : $archive->addFile($path);

            $progress->advance();
        }

        if (!is_null($db = $this->database())) {
            $archive->addFile($db['path'], $db['name']);
        }

        $progress->finish();

        $this->info(PHP_EOL.'Application and database backup successfully.');
    }

    /**
     * Get directories which are in include path.
     *
     * @return Generator
     */
    protected function getIncludes(): Generator
    {
        $dirs = $this->getDirectories('includes');

        // yield directories which are not subdirectory
        $offset = 0;

        foreach ($dirs as $dir) {
            ++$offset;

            foreach (array_slice($dirs, $offset) as $against) {
                if (starts_with($dir, $against)) {
                    continue 2;
                }
            }

            yield $dir;
        }
    }

    /**
     * Get directories which are in exclude path and are subdirectory.
     *
     * @param string $parent
     *
     * @return array
     */
    public function getExcludes(string $parent): array
    {
        $dirs = $this->getDirectories('excludes');

        foreach ($dirs as $dir) {
            if (starts_with($dir, $parent)) {
                $result[] = trim(str_replace($parent, '', $dir), '/');
            }
        }

        return $result ?? [];
    }

    /**
     * Get directories from config and append "/" to the end of path.
     *
     * @param string $type
     *
     * @return array
     */
    protected function getDirectories(string $type): array
    {
        $dirs = array_map(function ($dir) {
            return sprintf('%s/', rtrim($dir, '/'));
        }, array_filter($this->config[$type], 'is_dir'));

        // sort directory length using desc
        usort($dirs, function($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        return $dirs;
    }

    /**
     * Backup database data and return file path.
     *
     * @return array|null
     */
    protected function database(): ?array
    {
        $dumper = $this->dumper();

        if (is_null($dumper)) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), str_random(6));

        $key = sprintf('database.connections.%s', config('database.default'));

        $db = config($key);

        $dumper->setDbName($db['database'])
            ->setUserName($db['username'])
            ->setPassword($db['password'])
            ->useCompressor(new GzipCompressor)
            ->dumpToFile($path);

        return [
            'path' => $path,
            'name' => sprintf('%s.sql.gz', $db['database']),
        ];
    }

    /**
     * Get database dumper, return null if it is not supported.
     *
     * @return DbDumper|null
     */
    protected function dumper(): ?DbDumper
    {
        $mapping = [
            'mysql' => 'MySql',
            'mariadb' => 'MySql',
            'mongodb' => 'MongoDb',
            'sqlite' => 'Sqlite',
            'pgsql' => 'PostgreSql',
            'postgresql' => 'PostgreSql',
        ];

        $key = strtolower(config('database.default'));

        if (!isset($mapping[$key])) {
            $this->warn(sprintf('Not supported database type: "%s"', $key));

            return null;
        }

        $class = sprintf('\Spatie\DbDumper\Databases\%s', $mapping[$key]);

        return new $class;
    }
}
