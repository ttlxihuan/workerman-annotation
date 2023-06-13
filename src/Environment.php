<?php

/*
 * 环境处理
 */

namespace WorkermanAnnotation;

class Environment {

    /**
     * @var array 全局通用配置
     */
    private static $global = [];

    /**
     * @var array 全局通用键名
     */
    private static $globalKeys = ['REGISTER_ADDR', 'GATEWAY_LISTEN'];

    /**
     * 获取环境数据
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        $value = getenv($key) ?: $default;
        if (strcasecmp((string) $value, 'false') === 0) {
            return false;
        } elseif (strcasecmp((string) $value, 'true') === 0) {
            return true;
        } elseif (strcasecmp((string) $value, 'null') === 0) {
            return null;
        }
        return $value;
    }

    /**
     * 设置环境数据
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value = null) {
        putenv("{$key}={$value}");
    }

    /**
     * 获取全局通用配置
     * @staticvar array $registerAddress
     * @return type
     */
    public static function getGlobalConfig(string $key, $default = null) {
        return static::$global[$key] ?? $default;
    }

    /**
     * 加载环境数据
     * @param string $env
     */
    public static function load(string $env) {
        foreach (static::iteration($env) as $key => $value) {
            if (getenv($key) === false) {
                static::set($key, $value);
            }
        }
        // 加载全局通用环境配置数据
        if (workerEnv('APP_NODE')) {
            foreach (glob(BASE_PATH . '/env/' . workerEnv('APP_ENV') . '-*.env') as $file) {
                foreach (static::iteration(basename($file, '.env')) as $key => $value) {
                    if (in_array($key, static::$globalKeys, true)) {
                        static::$global[$key][] = $value;
                    }
                }
            }
            static::$global = array_map('array_unique', static::$global);
        } else {
            foreach (static::$globalKeys as $key) {
                static::$global[$key][] = workerEnv($key);
            }
        }
    }

    /**
     * 迭代环境配置文件变量
     * @param string $env
     * @throws \Exception
     */
    public static function iteration(string $env) {
        $file = BASE_PATH . '/env/' . $env . '.env';
        if (file_exists($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $item) {
                $config = explode('=', trim($item), 2);
                if (strpos($config[0], '#') === 0 || count($config) != 2) {
                    continue;
                }
                yield $config[0] => $config[1];
            }
        } else {
            throw new \Exception("环境配置文件 {$env}.env 不存在");
        }
    }

}
