<?php

/*
 * 定义注解应用处理参数
 */

namespace WorkermanAnnotation\Annotations;

class DefineParam implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $parameters = [];
        foreach ($params as $item) {
            if (isset($parameters[$item['name']])) {
                throw new Exception("注解应用处理类参数名 {$item['name']} 重复");
            }
            $array = [];
            if (isset($item['type'])) {
                $array['type'] = $item['type'];
            }
            if (isset($item['default'])) {
                $array['default'] = $item['default'];
            }
            $parameters[$item['name']] = $array;
        }
        return ['params' => $parameters];
    }

}
