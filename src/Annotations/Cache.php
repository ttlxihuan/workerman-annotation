<?php

/*
 * 缓存处理注解
 */

namespace WorkermanAnnotation\Annotations;

use Closure;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="timeout", type="int", default=600) 指定缓存保存时长（秒）
 * @DefineParam(name="name", type="string", default="") 指定缓存保存配置名，不指定则为默认
 * @DefineParam(name="empty", type="bool", default=false) 是否缓存空值，空值以empty语句结果为准
 */
class Cache implements iAnnotation {

    /**
     * @var bool 事务操作处理器
     */
    private static $handles = [];

    /**
     * 设置缓存处理器
     * @param Closure $set
     * @param Closure $get
     */
    public static function addHandle(Closure $set, Closure $get) {
        static::$handles = compact('set', 'get');
    }

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        return [
            function(Closure $next, array $call_params, string $name) use($params) {
                if (count(static::$handles)) {
                    $key = $name . md5(serialize($call_params));
                    foreach ($params as $param) {
                        $data = static::$handles['get']($key, $param['name']);
                        if ($data !== null && $data !== false) {
                            return unserialize($data);
                        }
                    }
                    $result = $next();
                    foreach ($params as $param) {
                        if (!empty($result) || $param['empty']) {
                            static::$handles['set']($key, serialize($result), $param['timeout'], $param['name']);
                        }
                    }
                    return $result;
                }
                return $next();
            }
        ];
    }

}
