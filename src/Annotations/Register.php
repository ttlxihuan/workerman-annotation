<?php

/*
 * 注册注解处理
 */

namespace WorkermanAnnotation\Annotations;

use Exception;

class Register implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $annotations = [];
        foreach ($params as $item) {
            if (strpos($item['class'], '\\') === false && class_exists($class = __NAMESPACE__ . '\\' . $item['class'])) {
                $class = __NAMESPACE__ . '\\' . $item['class'];
            } else {
                $class = ltrim($item['class'], '\\');
            }
            $name = strpos($class, '\\') === false ? $class : substr(strrchr($class, '\\'), 1);
            if (isset($annotations[$name])) {
                throw new Exception("注册注解名 $name 已经占用");
            }
            $annotations[$name] = $input['parse']->parseDefine($class);
            $annotations[$name]['instance'] = new $item['class']();
        }
        return $annotations;
    }

}
