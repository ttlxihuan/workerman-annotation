<?php

/*
 * 日志记录
 */

namespace WorkermanAnnotation\Annotations;

use Exception;
use GatewayWorker\Lib\Context;
use GatewayWorker\BusinessWorker;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="timeout", type="float", default=0) 指定日志只记录处理超时调用
 */
class Log implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        if (!workerEnv('APP_DEBUG')) {
            return [];
        }
        $param = end($params);
        $timeout = $param['timeout'];
        return [
            function (array $call_params, \Closure $next, string $name) use ($timeout) {
                $start = microtime(true);
                try {
                    $result = $next();
                } catch (Exception $err) {
                    $result = $err;
                }
                $time = bcsub($start, microtime(true), 6);
                if (bccomp($time, $timeout, 6) >= 0) {
                    $client_id = Context::$client_id;
                    $params = [];
                    foreach ($call_params as $param) {
                        $params[] = json_encode($param, JSON_UNESCAPED_UNICODE);
                    }
                    BusinessWorker::log(" $client_id => {$name}(" . implode(', ', $params) . ") {$time}s");
                }
                if ($result instanceof Exception) {
                    throw $result;
                }
                return $result;
            }
        ];
    }

}
