<?php

namespace LaravelAddons\CommandDaemonizer;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\InteractsWithTime;

class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'daemon-command:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart daemonized commands';

    /**
     * Execute the console command.
     *
     * @param CacheContract $cache
     */
    public function handle(CacheContract $cache): void
    {
        $cache->forever('laravel-addons:command-daemonizer:restart', $this->currentTime());

        $this->info('Broadcasting daemon command restart signal.');
    }
}
