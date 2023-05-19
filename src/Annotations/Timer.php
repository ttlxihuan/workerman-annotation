<?php

/*
 * 定时器注解处理
 */

namespace WorkermanAnnotation\Annotations;

use Workerman\Lib\Timer as TimerRun;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="id", type="int", default=0)  指定定时器启用进程号，为负数则所有进程号
 * @DefineParam(name="interval", type="int", default=1) 指定定时间隔时长（秒）
 * @DefineParam(name="persistent", type="bool", default=true) 是否为持久定时（是否为循环定时器）
 * @DefineParam(name="basis", type="string", default='') 指定起始计算时间，用于标准时间定时器
 * @DefineParam(name="worker", type="string", default='') 指定定时器启动业务名，不指定通用
 */
class Timer implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $parse = $input['parse'];
        $method = $parse->getRefName($input['ref']);
        foreach ($params as $param) {
            $parse->addCall(function (array &$call_params, \Closure $next)use ($parse, $method, $param) {
                list($name, $id) = $call_params;
                if (($param['worker'] && $param['worker'] !== $name) || ($id >= 0 && $id != $param['id'])) {
                    goto NEXT_TIMER;
                }
                $interval = $param['interval'];
                if ($interval <= 0) {
                    $parse->call($method);
                    goto NEXT_TIMER;
                }
                $persistent = $param['persistent'];
                if ($basis = $param['basis']) { // 基准时间
                    if (!preg_match('/(2[0-3]|[0-1]\d):[0-5]\d:[0-5]\d/', $basis)) {
                        throw new \Exception('定时器基础必需是有效时:分:秒');
                    }
                    $timer_id = TimerRun::add(1, function ()use ($parse, $method, $basis, $interval, $persistent, &$timer_id) {
                                static $prev = null, $tomorrow = null;
                                $now = time();
                                if ($prev === null) {
                                    if ($tomorrow) {
                                        $start = $tomorrow;
                                        $prev = $start + $interval;
                                    } else {
                                        $start = strtotime(date('Y-m-d ', $now) . $basis);
                                        $prev = $start + floor(($now - $start) / $interval + 1) * $interval;
                                    }
                                    $tomorrow = strtotime('today', $start + ($interval >= 86400 ? $interval : 86400));
                                }
                                if ($prev <= $now) {
                                    $parse->call($method);
                                    if (!$persistent) {
                                        TimerRun::del($timer_id);
                                        return;
                                    }
                                    $prev += $interval;
                                    if ($prev > $tomorrow) {
                                        $prev = null;
                                    }
                                }
                            });
                } else {
                    TimerRun::add($interval, function ()use ($parse, $method) {
                        $parse->call($method);
                    }, [], $persistent);
                }
                NEXT_TIMER:
                $next();
            });
        }
        return [];
    }

}
