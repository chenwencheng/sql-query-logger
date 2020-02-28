<?php
namespace Kdd\SqlQueryLogger\Providers;

class LumenServiceProvider extends ServiceProvider
{
    protected function setConfig()
    {
        if (!$this->app['config']->has('app.debug')) {

            $this->app['config']->set('app.debug', env('APP_DEBUG'));
        }
        $source = realpath(__DIR__ . '/../../config/sql-logger.php');

        $this->mergeConfigFrom($source, 'sql-logger');
    }
}
