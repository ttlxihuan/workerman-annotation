<?php

/*
 * 中间件定义注解处理
 */

namespace WorkermanAnnotation\Annotations;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="name", type="string", default='') 定义中间件名
 */
class Middleware implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $indexs = [];
        $method = $input['ref']->getName();
        foreach ($params as $param) {
            $indexs[] = $param['name'] ?: $method;
        }
        return [
            'middleware' => $indexs
        ];
    }

}
