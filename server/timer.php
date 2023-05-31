<?php

/**
 * 业务定时处理服务启动文件
 * 定时器剥离出业务服可缓解相互影响
 */
use \Workerman\Worker;
use WorkermanAnnotation\Event;
use WorkermanAnnotation\AnnotationHandle;

require_once __DIR__ . '/../bootstrap.php';

// 分布时此文件启动按需使用
if (!workerConfig('server.timer.active', true)) {
    return;
}

(function () {
    // Worker 进程
    $worker = new Worker();
    // worker名称
    $worker->name = workerConfig('server.timer.name');
    // 定时器处理进程数
    $worker->count = getTimerCount() ?: 1;
    // 网关管理，修改服务注册地址
    \GatewayWorker\Lib\Gateway::$registerAddress = getAllRegisterAddresses();
    // 日志处理
    Worker::$logFile = BASE_PATH . '/logs/timer.log';

    // 全局定时器启动
    $config = workerConfig('annotation.timer');
    if (is_array($config)) {
        $timer = new AnnotationHandle(...$config);
    } else {
        throw new \Exception('请配定时器注解信息');
    }
    $worker->onWorkerStart = function (Worker $worker)use ($timer) {
        $timer->call('@', $worker->name, getTimerCount() > 0 ? $worker->id : -1);
    };
})();

Event::init();

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
