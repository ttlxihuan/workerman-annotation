<?php

/*
 * 缓存处理注解
 */

namespace WorkermanAnnotation\Annotations;

use Closure;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="timeout", type="int", default="600") 指定缓存保存时长（秒）
 * @DefineParam(name="name", type="string", default="") 指定缓存保存配置名，不指定则为默认
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
        $timeout = 600;
        $name = null;
        foreach ($params as $param) {
            $timeout = $param['timeout'];
            $name = $param['name'];
        }
        $method = $input['parse']->getRefName($input['ref']);
        return [
            function(array $params, Closure $next) use($timeout, $method, $name) {
                if (count(static::$handles)) {
                    $key = $method . md5(serialize($params));
                    $data = static::$handles['get']($key, $name);
                    if (is_null($data) || $data === false) {
                        $result = $next();
                        static::$handles['set']($key, serialize($result), $timeout, $name);
                    } else {
                        $result = unserialize($data);
                    }
                    return $result;
                }
                return $next();
            }
        ];
    }

}
