<?php

/*
 * http协议注解
 * 定义为http协议处理控制器方法
 */

namespace WorkermanAnnotation\Annotations;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="type", type="string", default="") 限制请求类型，多个可以使用逗号分开，为空则所有请求类型
 * @DefineParam(name="name", type="string", default="") 指定请求名（不指定为当前方法名），整个路由是前段路径+请求名，连接时无分隔符
 */
class HttpMethod implements iAnnotation {

    /**
     * @var array 路由信息
     */
    protected $router = [];

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $indexs = [];
        $name = $input['ref']->getName();
        foreach ($input['indexs']['http'] ?? [''] as $before) {
            foreach ($params as $param) {
                foreach (explode(',', $param['type']) as $type) {
                    $indexs[] = strtoupper(trim($param['type'])) . $before . ($param['name'] ?: $name);
                }
            }
        }
        return [
            'http-router' => $indexs
        ];
    }

}
