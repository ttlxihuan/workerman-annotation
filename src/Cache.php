<?php

/*
 * 缓存处理
 */

namespace WorkermanAnnotation;

class Cache {

    /**
     * @var array 连接生成处理器，内部全部是匿名函数
     */
    protected static $makes = [];

    /**
     * @var array 已经生成的连接
     */
    protected static $connections = [];

    /**
     * 添加连接生成器
     * @param string $driver
     * @param \Closure $callback
     */
    public static function addMakeConnection(string $driver, \Closure $callback) {
        static::$makes[$driver] = $callback;
    }

    /**
     * 缓存连接
     * @param string $name
     * @return mixed
     */
    public static function connection(string $name = null) {
        if (is_null($name)) {
            $name = workerConfig('cache.default');
        }
        if (empty(static::$connections[$name])) {
            $options = workerConfig("cache.stores.$name");
            if (empty(static::$makes[$options['driver']])) {
                throw new \Exception("缓存连接 {$name} 未正确配置连接处理器");
            }
            static::$connections[$name] = static::$makes[$options['driver']]($options);
        }
        return static::$connections[$name];
    }

    /**
     * 调用缓存
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments) {
        return static::connection()->$name(...$arguments);
    }

}
