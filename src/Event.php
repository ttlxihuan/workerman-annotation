<?php

/*
 * 服务事件处理类
 */

namespace WorkermanAnnotation;

use \GatewayWorker\Lib\Gateway;
use GatewayWorker\BusinessWorker;

class Event {

    /**
     * @var Annotation 控制器集
     */
    public static $controllers;

    /**
     * @var BusinessWorker 业务处理服务实例
     */
    public static $businessWorker;

    /**
     * 初始化处理
     */
    public static function init() {
        // 控制器加载
        $config = workerConfig('annotation.controller');
        if (is_array($config)) {
            static::$controllers = new AnnotationHandle(...$config);
        } else {
            throw new \Exception('请配置控制器注解信息');
        }
    }

    /**
     * 当子进程启动后触发，只有一次
     * 每个子进程ID值不一样，可用来区分做不同的任务
     * 
     * @param BusinessWorker $businessWorker 子进程实例
     */
    public static function onWorkerStart(BusinessWorker $businessWorker) {
        static::$businessWorker = $businessWorker;
        static::$controllers->callIndex('bind-call', 'start', $businessWorker->id);
    }

    /**
     * 当子进程退出后触发，只有一次
     * 每个子进程ID值不一样，可用来区分做不同的任务
     * 
     * @param BusinessWorker $businessWorker 子进程实例
     */
    public static function onWorkerStop(BusinessWorker $businessWorker) {
        static::$controllers->callIndex('bind-call', 'stop', $businessWorker->id);
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        static::$controllers->callIndex('bind-call', 'connect', $client_id);
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message) {
        $result = static::$controllers->call('@', $message);
        if ($result !== null) {
            Gateway::sendToCurrentClient($result);
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id) {
        static::$controllers->callIndex('bind-call', 'close', $client_id);
    }

    /**
     * WebSocket 连接时回调
     * @param string $client_id
     * @param mixed $data
     */
    public static function onWebSocketConnect($client_id, $data) {
        static::$controllers->callIndex('bind-call', 'connect', $client_id, $data);
    }

}
