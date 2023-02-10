<?php

/**
 * 业务处理服务启动文件
 */
use \Workerman\Worker;
use WorkermanAnnotation\Event;
use \GatewayWorker\BusinessWorker;

require_once __DIR__ . '/../bootstrap.php';

// 分布时此文件启动按需使用
if (!workerConfig('server.worker.active', true)) {
    return;
}

(function() {
    $registerAddress = workerConfig('server.register_addresses');
    if (empty($registerAddress)) {
        $registerAddress = [workerConfig('server.register.addr')];
    }
    // bussinessWorker 进程
    $worker = new BusinessWorker();
    // worker名称
    $worker->name = workerConfig('server.worker.name');
    // bussinessWorker进程数量
    $worker->count = defined('PROCESS_NUM') ? PROCESS_NUM : 1;
    // 服务注册地址
    $worker->registerAddress = $registerAddress;
    // 事件处理
    $worker->eventHandler = Event::class;

    // 网关管理，修改服务注册地址
    \GatewayWorker\Lib\Gateway::$registerAddress = $worker->registerAddress;

    // 日志处理
    BusinessWorker::$logFile = BASE_PATH . '/logs/business-worker.log';
})();

Event::init();

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
