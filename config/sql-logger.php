<?php

return [
    /**
     * 是否启用sql查询日志
     */
    'enabled' => env('SQL_LOG_ENABLED', true),

    /**
     * artisan 命令行下的sql查询使用单独的日志文件
     */
    'console_separate_file' => env('SQL_LOG_SEPARATE_ARTISAN', true),

    /**
     * 是否记录慢查询
     */
    'slow_query_enabled' => env('SQL_LOG_SLOW_QUERY_ENABLED', true),

    /**
     * 查询时间大于多少毫秒认定为慢查询
     */
    'slow_query_min_exec_time' => env('SQL_LOG_SLOW_QUERY_MIN_EXEC_TIME', 500),

    /**
     * 是否覆盖式写日志
     */
    'override' => env('SQL_LOG_OVERRIDE', false),

    /**
     * sql日志存放目录
     */
    'directory' => storage_path(env('SQL_LOG_DIRECTORY', 'logs/sql')),

    /**
     * 是否把毫秒转换为秒记录
     */
    'convert_to_seconds' => env('SQL_LOG_CONVERT_TO_SECONDS', false),
];
