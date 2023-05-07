<?php

/*
 * 日志记录
 */

namespace WorkermanAnnotation\Annotations;

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
        $param = end($params);
        $timeout = $param['timeout'];
        $parameter = $param['parameter'];
        return [
            function(Closure $next, array $call_params, string $name) use($timeout, $parameter) {
                $start = microtime(true);
                $result = $next();
                $time = bcsub($start, microtime(true), 6);
                if (bccomp($time, $timeout, 6) >= 0) {
                    $client_id = Context::$client_id;
                    $params = [];
                    foreach ($call_params as $param) {
                        if (is_object($param)) {
                            $params[] = 'object(' . get_class($param) . ')';
                        } else {
                            $params[] = var_export($call_params, true);
                        }
                    }
                    BusinessWorker::log(" $client_id => {$name}(  " . implode(', ', $params) . " ) {$time}s");
                }
                return $result;
            }
        ];
    }

}
