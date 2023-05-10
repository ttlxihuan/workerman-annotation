<?php

/*
 * 环境处理
 */

namespace WorkermanAnnotation;

class Environment {

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
     * 加载环境数据
     * @param string $env
     */
    public static function load(string $env) {
        foreach (static::iteration($env) as $key => $value) {
            static::set($key, $value);
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
            $array = explode("\n", file_get_contents($file));
            foreach ($array as $item) {
                $config = explode('=', trim($item), 2);
                if (strpos($config[0], '#') === 0 || getenv($config[0]) !== false || count($config) != 2) {
                    continue;
                }
                yield $config[0] => $config[1];
            }
        } else {
            throw new \Exception("环境配置文件 {$env}.env 不存在");
        }
    }

}
