<?php

/*
 * 配置处理文件
 */

namespace WorkermanAnnotation;

class Config {

    /**
     * @var array 配置数据源
     */
    protected static $data = [];

    /**
     * 获取配置数据
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        if (is_null($key) || $key === '') {
            return static::$data;
        }
        if (isset(static::$data[$key])) {
            return static::$data[$key];
        }
        $data = static::$data;
        foreach (explode('.', $key) as $key) {
            if (isset($data[$key])) {
                $data = $data[$key];
            } else {
                return $default;
            }
        }
        return $data;
    }

    /**
     * 设置配置数据
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value = null) {
        $data = &static::$data;
        if (is_null($key)) {
            $prev = &$data;
        } else {
            foreach (explode('.', $key) as $key) {
                if (!isset($data[$key])) {
                    $data[$key] = [];
                }
                $prev = &$data;
                $data = &$data[$key];
            }
        }
        if (is_null($value)) {
            unset($prev[$key]);
        } else {
            $data = $value;
        }
    }

    /**
     * 加载配置目录
     * @param string $dir
     */
    public static function load(string $dir) {
        if (is_dir($dir) && file_exists($dir)) {
            $dir = realpath($dir);
            foreach (glob($dir . '/*.php') as $file) {
                $key = substr(str_replace($dir, '', realpath($file)), 1, -4);
                static::set(str_replace(['\\', '/'], '.', $key), include $file);
            }
        }
    }

}
