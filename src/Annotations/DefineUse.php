<?php

/*
 * 定义注解应用处理
 */

namespace WorkermanAnnotation\Annotations;

class DefineUse implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $parameters = [];
        foreach ($params as $item) {
            $parameters = array_merge($parameters, $item);
        }
        return $parameters;
    }

}
