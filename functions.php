<?php

/*
 * 助手函数库
 */

use Workerman\Worker;
use WorkermanAnnotation\Config;
use WorkermanAnnotation\Environment;
use WorkermanAnnotation\Annotations\HttpRouter;

/**
 * 启动服务
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function serverRun($basePath = null) {
    ini_set('display_errors', 'on');
    if ($basePath) {
        Environment::set('BASE_PATH', $basePath);
    }
    if (strpos(strtolower(PHP_OS), 'win') === 0) {
        global $argv;
        unset($argv[0]);
        chdir(__DIR__ . '/server/');
        foreach (['register', 'gateway', 'worker'] as $server) {
            $argv[] = "{$server}.php";
        }
    } else {
        // 必要扩展
        if (!extension_loaded('pcntl')) {
            exit("请安装 pcntl 扩展再启动服务。 安装说明地址： http://doc3.workerman.net/appendices/install-extension.html\n");
        }
        if (!extension_loaded('posix')) {
            exit("请安装 posix 扩展再启动服务。 安装说明地址： http://doc3.workerman.net/appendices/install-extension.html\n");
        }
        // 获取进程数
        define('PROCESS_NUM', max(2, substr_count(file_get_contents("/proc/cpuinfo"), "processor")));
        // 标记是全局启动
        define('GLOBAL_START', 1);
        // 加载所有 start_*.php 启动文件，以便启动所有服务
        foreach (glob(__DIR__ . '/server/*.php') as $server) {
            require_once $server;
        }
    }
    // 运行所有服务
    Worker::runAll();
}

/**
 * 获取环境数据
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function workerEnv(string $key, $default = null) {
    return Environment::get($key, $default);
}

/**
 * 获取配置数据
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function workerConfig(string $key = null, $default = null) {
    return Config::get($key, $default);
}

/**
 * 获取控制台参数
 * @param string $name
 * @param mixed $default
 * @return mixed
 */
function consoleArgv(string $name, $default = null) {
    global $argc, $argv;
    for ($key = 1; $key < $argc; $key++) {
        $option = $argv[$key];
        if (strpos($option, "--$name=") === 0) {
            list(, $value) = explode('=', $option, 2);
        } elseif ($option === "--$name") {
            $value = $argv[++$key] ?? '';
            // 如果值是参数结构则不认为是指定值
            if (strpos($value, '-') === 0) {
                unset($value);
                $key--;
            }
        }
    }
    return $value ?? $default;
}

/**
 * 获取当前请求处理器
 * @return Request|null
 */
function getRequest() {
    return HttpRouter::$request;
}
