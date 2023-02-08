<?php

/*
 * 初始化引导处理
 */

// 自动加载类
require_once __DIR__ . '/../../autoload.php';

defined('BASE_PATH') || define('BASE_PATH', workerEnv('BASE_PATH', realpath(__DIR__ . '/../../../')));

(function() {
    // 环境变量加载
    $env_name = workerEnv('APP_ENV', consoleArgv('env', 'production'));

    // 分布式处理，需要配置多个环境变量文件，以适应不同和节点启动
    $node = consoleArgv('node');
    if ($node) {
        $env_name .= "-$node";
    }

    \WorkermanAnnotation\Environment::load($env_name ?: 'production');

    $logDir = BASE_PATH . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir);
    }
})();

// 配置加载
\WorkermanAnnotation\Config::load(BASE_PATH . '/config');
