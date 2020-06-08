<?php

namespace LaravelAddons\CommandDaemonizer;

use Illuminate\Support\ServiceProvider;

class CommandDaemonizerServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    RestartCommand::class,
                ]
            );
        }
    }
}
