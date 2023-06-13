<?php

/*
 * Websocket协议注解
 * 定义为Websocket协议处理控制器方法
 */

namespace WorkermanAnnotation\Annotations;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="name", type="string", default='') 指定请求名，整个路由是前段路径+请求名，连接时无分隔符
 */
class WebsocketMethod implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        if (!hasGatewayProtocol('websocket', 'ws')) {
            return [];
        }
        $indexs = [];
        $method = $input['ref']->getName();
        foreach ($input['indexs']['websocket'] ?? [''] as $before) {
            foreach ($params as $param) {
                $indexs[] = $before . ($param['name'] ?: $method);
            }
        }
        return [
            'websocket-router' => $indexs
        ];
    }

}
