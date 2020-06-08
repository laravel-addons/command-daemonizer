<?php

namespace LaravelAddons\CommandDaemonizer;

class WorkerOptions
{
    /**
     * Indicates if the worker should run in maintenance mode.
     *
     * @var bool
     */
    private $force;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * @var int
     */
    private $memory;

    /**
     * The number of seconds to wait in between loop iterations.
     *
     * @var int
     */
    private $sleep;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * @var int
     */
    private $timeout;

    /**
     * WorkerOptions constructor.
     *
     * @param bool $force
     * @param int $memory
     * @param int $sleep
     * @param int $timeout
     */
    public function __construct(bool $force = false, int $memory = 128, int $sleep = 0, int $timeout = 60)
    {
        $this->force = $force;
        $this->memory = $memory;
        $this->sleep = $sleep;
        $this->timeout = $timeout;
    }

    /**
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * @return int
     */
    public function getMemory(): int
    {
        return $this->memory;
    }

    /**
     * @return int
     */
    public function getSleep(): int
    {
        return $this->sleep;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
