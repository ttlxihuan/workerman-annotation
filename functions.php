<?php

/*
 * 助手函数库
 */

use Workerman\Worker;
use WorkermanAnnotation\Event;
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
        require_once __DIR__ . '/bootstrap.php';
        global $argv;
        unset($argv[0]);
        chdir(__DIR__ . '/server/');
        foreach (['register', 'gateway', 'timer', 'worker'] as $server) {
            // 分布时此文件启动按需使用
            if (workerConfig("server.{$server}.active", true)) {
                $argv[] = "{$server}.php";
            }
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
 * 获取所有的注册服务地址集
 * @return array
 */
function getAllRegisterAddresses() {
    $registerAddress = Environment::getGlobalConfig('REGISTER_ADDR');
    if (empty($registerAddress)) {
        exit("未能获取到注册服地址，请核对环境配置文件。\n");
    }
    return $registerAddress;
}

/**
 * 获取网关对外协议
 * @return bool
 */
function hasGatewayProtocol(string ...$names) {
    static $protocol = null;
    if (empty($protocol)) {
        $gatewayListen = Environment::getGlobalConfig('GATEWAY_LISTEN');
        if (empty($gatewayListen)) {
            exit("未能获取到网关对外监听地址，请核对环境配置文件。\n");
        }
        // 对外协议不能有多个，否则容易造成异常
        foreach ($gatewayListen as $addr) {
            $protocol = strtolower(parse_url($addr, PHP_URL_SCHEME));
        }
    }
    foreach ($names as $name) {
        if ($name == $protocol) {
            return true;
        }
    }
    return false;
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

/**
 * 获取业务处理进程ID
 */
function getWorkerId() {
    return Event::$businessWorker ? Event::$businessWorker->id : -1;
}

/**
 * 获取业务处理进程数
 */
function getWorkerCount() {
    return defined('GLOBAL_START') ? workerConfig('server.worker.count', PROCESS_NUM * 6) : 1;
}

/**
 * 获取定时处理进程数
 */
function getTimerCount() {
    return defined('GLOBAL_START') ? workerConfig('server.timer.count', 1) : 0;
}

/**
 * 获取网关进程数
 */
function getGatewayCount() {
    return defined('GLOBAL_START') ? workerConfig('server.worker.count', PROCESS_NUM * 2) : 1;
}
