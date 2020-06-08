<?php

namespace LaravelAddons\CommandDaemonizer;

use BadMethodCallException;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheContract;

abstract class DaemonCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'daemon-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start command as a daemon';

    /**
     * DaemonCommand constructor.
     */
    public function __construct()
    {
        $this->checkMethod('daemon');
        $this->appendOptions();

        parent::__construct();
    }

    /**
     * @param Worker $worker
     * @param CacheContract $cache
     */
    public function handle(Worker $worker, CacheContract $cache): void
    {
        $this->init();
        $worker->setCache($cache);
        $worker->daemon($this, $this->gatherWorkerOptions());
    }

    /**
     * For override in inherited classes
     */
    protected function init(): void
    {
        //
    }

    /**
     * @param string $method
     */
    protected function checkMethod(string $method): void
    {
        if (!method_exists($this, $method)) {
            throw new BadMethodCallException(
                sprintf('Method %s:%s not found', static::class, $method)
            );
        }
    }

    /**
     * Append default options necessary for smart command
     */
    protected function appendOptions(): void
    {
        $this->signature .= '
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=0 : Number of seconds to sleep at each iteration in a loop}
                            {--timeout=60 : The number of seconds a child process can run}
                            ';
    }

    /**
     * Gather all of the command worker options as a single object.
     *
     * @return WorkerOptions
     */
    protected function gatherWorkerOptions(): WorkerOptions
    {
        return new WorkerOptions(
            $this->option('force'),
            $this->option('memory'),
            $this->option('sleep'),
            $this->option('timeout')
        );
    }
}
