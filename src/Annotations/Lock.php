<?php

/*
 * 锁注解，用于并发进程互斥执行处理
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\BusinessException;
use WorkermanAnnotation\Cache as CacheRun;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="timeout", type="int", default=30) 指定缓存保存时长（秒）
 * @DefineParam(name="wait", type="bool", default=true) 是否等待取锁
 */
class Lock implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $param = end($params);
        $timeout = $param['timeout'];
        $wait = $param['wait'];
        return [
            function(Closure $next, array $call_params, string $name) use($timeout, $wait) {
                $this->lock($name, $timeout, $wait);
                $result = $next();
                $this->unlock($name);
                return $result;
            }
        ];
    }

    /**
     * 获取锁处理
     * @param string $name
     * @param int $timeout
     * @param bool $wait
     * @return bool
     * @throws BusinessException
     */
    public function lock(string $name, int $timeout, bool $wait) {
        $value = md5(uniqid(microtime(true)));
        WAIT_LOCK:
        if (CacheRun::set("lock-{$name}", $value, 'NX', 'EX', $timeout)) {
            return true;
        }
        if ($wait) {
            usleep(100);
            goto WAIT_LOCK;
        }
        return false;
    }

    /**
     * 释放锁处理
     * @param string $name
     * @return void
     */
    public function unlock(string $name) {
        CacheRun::del("lock-{$name}");
    }

}
