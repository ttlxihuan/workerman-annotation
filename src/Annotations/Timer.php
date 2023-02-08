<?php

/*
 * 定时器注解处理
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\Event;
use Workerman\Lib\Timer as TimerRun;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="id", type="int", default=0)  指定定时器启用进程号，为负数则所有进程号
 * @DefineParam(name="interval", type="int", default=1) 指定定时间隔时长（秒）
 * @DefineParam(name="persistent", type="bool", default=true) 是否为持久定时（是否为循环定时器）
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
            if ($param['interval'] > 0 && $param['id'] != Event::$businessWorker->id) {
                continue;
            }
            TimerRun::add($param['interval'], function()use($parse, $method) {
                $parse->call($method);
            }, $param['persistent']);
        }
        return [];
    }

}
