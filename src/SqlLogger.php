<?php

namespace Kdd\SqlQueryLogger;

class SqlLogger
{
    /**
     * Application version.
     *
     * @var string
     */
    protected $version;

    /**
     * 是否启用sql日志
     *
     * @var bool
     */
    protected $enabled;

    /**
     * 是否启用慢查询日志
     *
     * @var bool
     */
    protected $slowQueryEnabled;

    /**
     * 慢查询最小执行时间
     *
     * @var float
     */
    protected $slowLogTime;

    /**
     * 是否覆盖式写日志
     *
     * @var bool
     */
    protected $override;

    /**
     * 日志存放目录
     *
     * @var string
     */
    protected $directory;

    /**
     * 是否把毫秒转换为秒记录
     *
     * @var bool
     */
    protected $convertToSeconds;

    /**
     * artisan 命令行下的sql查询使用单独的日志文件
     *
     * @var bool
     */
    protected $separateConsoleLog;

    /**
     * 本次声明周期id
     *
     * @var string
     */
    protected $uniqid;

    /**
     * SqlLogger constructor.
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app                  = $app;
        $config                     = $this->app['config']->get('sql-logger');
        $this->enabled              = data_get($config, 'enabled');
        $this->slowQueryEnabled     = data_get($config, 'slow_query_enabled');
        $this->slowLogTime          = data_get($config, 'slow_query_min_exec_time');
        $this->override             = data_get($config, 'override');
        $this->directory            = rtrim(data_get($config, 'directory'), '\\/');
        $this->convertToSeconds     = data_get($config, 'convert_to_seconds');
        $this->separateConsoleLog   = data_get($config, 'console_separate_file');
        $this->uniqid               = strtoupper(date('YmdHis') . uniqid());
    }

    /**
     * Log query.
     *
     * @param mixed $query
     * @param mixed $bindings
     * @param mixed $time
     */
    public function log($query, $bindings, $time)
    {
        static $queryNr = 0;

        ++$queryNr;

        try {

            list($sqlQuery, $execTime)  = $this->getSqlQuery($query, $bindings, $time);
        } catch (\Exception $e) {

            $this->app->log->notice("SQL query {$queryNr} cannot be bound: " . $query);
            return;
        }

        $logData    = $this->getLogData($queryNr, $sqlQuery, $execTime);
        $this->save($logData, $execTime, $queryNr);
    }

    /**
     * Save data to log file.
     *
     * @param string $data
     * @param int    $execTime
     * @param int    $queryNr
     */
    protected function save($data, $execTime, $queryNr)
    {
        $filePrefix = ($this->separateConsoleLog && $this->app->runningInConsole()) ? '-artisan' : '';

        $this->createLogDirectoryIfNeeded($queryNr, $execTime);

        // 记录普通sql日志
        if ($this->enabled) {

            $this->saveLog($data, date('Y-m-d') . $filePrefix . '-log.sql', ($queryNr == 1 && (bool) $this->override));
        }

        // 记录慢查询日志
        if ($this->shouldLogSlowQuery($execTime)) {

            $this->saveLog($data, date('Y-m-d') . $filePrefix . '-slow-log.sql');
        }
    }

    /**
     * 是否记录慢查询日志
     *
     * @param float $execTime
     *
     * @return bool
     */
    protected function shouldLogSlowQuery($execTime)
    {
        return $this->slowQueryEnabled && $execTime >= $this->slowLogTime;
    }

    /**
     * 写文件
     *
     * @param string $data
     * @param string $fileName
     * @param bool   $override
     */
    protected function saveLog($data, $fileName, $override = false)
    {
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . $fileName, $data, $override ? 0 : FILE_APPEND);
    }

    /**
     * 日志内容
     *
     * @param int    $queryNr
     * @param string $query
     * @param float  $execTime
     *
     * @return string
     */
    protected function getLogData($queryNr, $query, $execTime)
    {
        $time       = $this->convertToSeconds ? ($execTime / 1000.0) . '.s' : $execTime . 'ms';
        $datetime   = date('Y-m-d H:i:s');
        return      "[UNIQID:{$this->uniqid}({$queryNr})({$datetime})({$time})]\t{$query}" . PHP_EOL;
    }

    /**
     * sql和执行时间
     *
     * @param mixed $query
     * @param mixed $bindings
     * @param mixed $execTime
     *
     * @return array
     */
    protected function getSqlQuery($query, $bindings, $execTime)
    {
        if (version_compare($this->getVersion(), '5.2.0', '>=')) {

            $bindings   = $query->bindings;
            $execTime   = $query->time;
            $query      = $query->sql;
        }

        foreach ($bindings as $i => $binding) {

            if ($binding instanceof \DateTime) {

                $bindings[$i] = $binding->format('Y-m-d H:i:s');
            } elseif (is_string($binding)) {

                $bindings[$i] = str_replace("'", "\\'", $binding);
            }
        }

        $query      = str_replace(['%', '?', "\\n"], ['%%', "'%s'", ' '], $query);
        $fullSql    = vsprintf($query, $bindings);

        return      [$fullSql, $execTime];
    }

    /**
     * Get framework version.
     *
     * @return string
     */
    protected function getVersion()
    {
        $version    = $this->app->version();

        if (mb_strpos($version, 'Lumen') !== false) {

            $p  = mb_strpos($version, '(');
            $p2 = mb_strpos($version, ')');
            if ($p !== false && $p2 !== false) {

                $version = trim(mb_substr($version, $p + 1, $p2 - $p - 1));
            }
        }

        return  $version;
    }

    /**
     * 创建目录
     *
     * @param int $queryNr
     * @param int $execTime
     */
    protected function createLogDirectoryIfNeeded($queryNr, $execTime)
    {
        if ($queryNr == 1 && !file_exists($this->directory) && ($this->enabled || $this->shouldLogSlowQuery($execTime))) {

            mkdir($this->directory, 0777, true);
        }
    }
}
