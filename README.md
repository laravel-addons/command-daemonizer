# Laravel Command Daemonizer

**Run a Laravel console command as a long-lived process with gracefully shutdown.**

### Installation
```shell script
composer require laravel-addons/command-daemonizer
```
Laravel will automatically add the service provider `LaravelAddons\CommandDaemonizer\CommandDaemonizerServiceProvider` to the file `config/app.php` in `providers` option.

In Lumen, you MUST manually register the service provider `\LaravelAddons\CommandDaemonizer\CommandDaemonizerServiceProvider` in `bootstrap/app.php` file:
```php
$app->register(LaravelAddons\CommandDaemonizer\CommandDaemonizerServiceProvider::class);
``` 

### How to use
For example, run kafka consumer
```php
use LaravelAddons\CommandDaemonizer\DaemonCommand;

class KafkaMessageConsumer extends DaemonCommand
{
    private $config;
    private $consumer;

    protected $signature = 'kafka-consumer';
    
    public function __construct(array $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    protected function init(): void
    {
        $this->consumer = ... //initialisation of consumer
    }

    public function daemon(MyHandler $handler, LoggerInterface $logger): void
    {
        $message = $this->consumer->receive();

        try {
            if ($message instanceof RdKafkaMessage) {
                $handler->handle($message);
                $this->consumer->acknowledge($message);
            }
        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }
    }
}
```
1. You MUST implement method `daemon()`. This method will run in an endless loop. 
2. You can use constructor to inject your dependency or inject dependency in `daemon()` method. Your dependencies will be resolved. 
3. You can override the empty parent method `init()` to run some code before starting the daemon.

### Options
`DaemonCommand` append some options to you command:
```
--force : Force the worker to run even in maintenance mode
--memory=128 : The memory limit in megabytes
--sleep=0 : Number of seconds to sleep at each iteration in a loop
--timeout=60 : The number of seconds a child process can run
```

### Gracefully shutdown
Since daemonized commands are long-lived processes, they will not pick up changes to your code without being restarted. So, the simplest way to deploy an application using daemonized commands is to restart the commands during your deployment process. You may gracefully restart all of the daemonized commands by issuing the `daemon-command:restart`:

```shell script
php artisan daemon-command:restart
```

This command will instruct all daemonized commands to gracefully "die" after they finish processing their current step in loop. Since the daemonized commands will die when the `daemon-command:restart` command is executed, you should be running a process manager such as [Supervisor](http://supervisord.org/) to automatically restart the daemonized commands.

This library uses the cache to store restart signals, so you should verify a cache driver is properly configured for your application before using this feature.

Based on [Illuminate Queue Worker](https://github.com/illuminate/queue/blob/master/Worker.php).
