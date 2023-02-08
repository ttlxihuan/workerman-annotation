<?php

/**
 * 服务注册服务启动处理
 */
use \Workerman\Worker;
use \GatewayWorker\Register;

require_once __DIR__ . '/../bootstrap.php';

// 分布时此文件一般只启动在一个节点中，当地址为空时则不启动
if (!workerConfig('server.register.active', true)) {
    return;
}
// register 必须是text协议
$register = new Register('text://' . workerConfig('server.register.addr'));

// 日志处理
Register::$logFile = BASE_PATH . '/logs/register.log';

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
