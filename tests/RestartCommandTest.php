<?php

namespace LaravelAddons\CommandDaemonizer\Test;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use LaravelAddons\CommandDaemonizer\RestartCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestartCommandTest extends TestCase
{
    public function testHandle(): void
    {
        $now = new Carbon('2020-06-07 12:34:56');
        $nowTimestamp = 1591533296;
        Carbon::setTestNow($now);

        /** @var Repository|MockObject $cache */
        $cache = $this->createMock(Repository::class);
        $cache->expects($this->once())
            ->method('forever')
            ->with('laravel-addons:command-daemonizer:restart', $nowTimestamp);

        /** @var RestartCommand|MockObject $command */
        $command = $this->getMockBuilder(RestartCommand::class)
            ->onlyMethods(['info'])
            ->getMock();

        $command->expects($this->once())
            ->method('info')
            ->with('Broadcasting daemon command restart signal.');

        $command->handle($cache);
    }
}
