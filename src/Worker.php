<?php

namespace LaravelAddons\CommandDaemonizer;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Events\Dispatcher;

class Worker
{
    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * The cache repository implementation.
     *
     * @var CacheContract
     */
    protected $cache;

    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Worker constructor.
     *
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Run command in a loop.
     *
     * @param Command $command
     * @param WorkerOptions $options
     */
    public function daemon(Command $command, WorkerOptions $options): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastCommandRestart();

        while (true) {
            // Before run command,
            // we will make sure that the application is currently not down for maintenance and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (!$this->daemonShouldRun($command, $options)) {
                $this->pauseWorker($options, $lastRestart);

                continue;
            }

            if ($this->supportsAsyncSignals()) {
                $this->registerTimeoutHandler($options);
            }

            // Call command method 'daemon' and inject its dependencies
            $command->getLaravel()->call([$command, 'daemon']);

            $this->sleep($options->getSleep());

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the command should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $lastRestart);
        }
    }

    /**
     * Register the worker timeout handler.
     *
     * @param WorkerOptions $options
     */
    protected function registerTimeoutHandler(WorkerOptions $options): void
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
        pcntl_signal(
            SIGALRM,
            function () {
                $this->kill(1);
            }
        );

        pcntl_alarm(
            max($options->getTimeout(), 0)
        );
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param Command $command
     * @param WorkerOptions $options
     *
     * @return bool
     */
    protected function daemonShouldRun(Command $command, WorkerOptions $options): bool
    {
        return !(($command->getLaravel()->isDownForMaintenance() && !$options->isForce()) || $this->paused);
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param WorkerOptions $options
     * @param int $lastRestart
     */
    protected function pauseWorker(WorkerOptions $options, int $lastRestart): void
    {
        $this->sleep($options->getSleep() > 0 ? $options->getSleep() : 1);

        $this->stopIfNecessary($options, $lastRestart);
    }

    /**
     * Stop the process if necessary.
     *
     * @param WorkerOptions $options
     * @param int|null $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, ?int $lastRestart): void
    {
        if ($this->shouldQuit) {
            $this->kill();
        }

        if ($this->memoryExceeded($options->getMemory())) {
            $this->stop(12);
        } elseif ($this->commandShouldRestart($lastRestart)) {
            $this->stop();
        }
    }

    /**
     * Determine if the command worker should restart.
     *
     * @param int|null $lastRestart
     *
     * @return bool
     */
    protected function commandShouldRestart(?int $lastRestart): bool
    {
        return $this->getTimestampOfLastCommandRestart() !== $lastRestart;
    }

    /**
     * Get the last command restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastCommandRestart(): ?int
    {
        if ($this->cache) {
            return $this->cache->get('laravel-addons:command-daemonizer:restart');
        }

        return null;
    }

    /**
     * Enable async signals for the process.
     */
    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(
            SIGTERM,
            function () {
                $this->shouldQuit = true;
            }
        );

        pcntl_signal(
            SIGUSR2,
            function () {
                $this->paused = true;
            }
        );

        pcntl_signal(
            SIGCONT,
            function () {
                $this->paused = false;
            }
        );
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param int $memoryLimit
     *
     * @return bool
     */
    public function memoryExceeded(int $memoryLimit): bool
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param int $status
     */
    public function stop(int $status = 0): void
    {
        $this->events->dispatch(new Events\WorkerStopping);

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param int $status
     */
    public function kill(int $status = 0): void
    {
        $this->events->dispatch(new Events\WorkerStopping);

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param int|float $seconds
     */
    public function sleep($seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Set the cache repository implementation.
     *
     * @param CacheContract $cache
     */
    public function setCache(CacheContract $cache): void
    {
        $this->cache = $cache;
    }
}
