<?php
namespace Kdd\SqlQueryLogger\Providers;

use Kdd\SqlQueryLogger\SqlLogger;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->setConfig();
        if ($this->app['config']->get('app.debug')) {

            if ($this->app['config']->get('sql-logger.enabled') || $this->app['config']->get('sql-logger.slow_query_enabled')) {

                $logger = new SqlLogger($this->app);
                $this->app['db']->listen(function ($query, $bindings = null, $time = null) use ($logger) {

                    $logger->log($query, $bindings, $time);
                });
            }
        }
    }

    protected function setConfig()
    {
        $source = realpath(__DIR__ . '/../../config/sql-logger.php');
        if ($this->app->runningInConsole()) {

            $this->publishes([
                $source => (function_exists('config_path') ? config_path('sql-logger.php') : base_path('config/sql-logger.php')),
            ]);
        }
        $this->mergeConfigFrom($source, 'sql-logger');
    }
}
