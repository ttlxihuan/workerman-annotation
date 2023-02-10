<?php

/*
 * 初始化引导处理
 */

use WorkermanAnnotation\Config;
use WorkermanAnnotation\Environment;

// 自动加载类
require_once __DIR__ . '/../../autoload.php';

defined('BASE_PATH') || define('BASE_PATH', workerEnv('BASE_PATH', realpath(__DIR__ . '/../../../')));

(function() {
    // 环境变量加载
    $env = workerEnv('APP_ENV') ?: consoleArgv('env', 'production');
    // 分布式处理，需要配置多个环境变量文件，以适应不同和节点启动
    $node = workerEnv('APP_NODE') ?: consoleArgv('node', '');
    if (empty($node)) {
        $env_name = "$env";
    } else {
        $env_name = "$env-$node";
    }
    // 加载环境配置文件
    Environment::load($env_name);
    Environment::set('APP_ENV', $env);
    Environment::set('APP_NODE', $node);
    // 加载配置文件
    Config::load(BASE_PATH . '/config');
    // 创建日志文件
    $logDir = BASE_PATH . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir);
    }
})();
