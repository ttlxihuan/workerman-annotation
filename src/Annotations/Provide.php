<?php

/*
 * 三方外部扩展包注解处理
 */

namespace WorkermanAnnotation\Annotations;

/**
 * @DefineUse(class=true)
 * @DefineParam(name="action", type="string") 指定外部扩展功能名，调用入口
 * @DefineParam(name="name", type="string") 指定外部扩展处理名
 */
class Provide implements iAnnotation {

    /**
     * @var array 提供记录暂存
     */
    private static $provides = [];

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        foreach ($params as $param) {
            $action = $param['action'];
            $name = $param['name'];
            if (empty(static::$provides[$action])) {
                $file = BASE_PATH . "/provides/{$name}.php";
                static::$provides[$action] = file_exists($file) && is_readable($file) && require $file;
            }
        }
        return [];
    }

}
