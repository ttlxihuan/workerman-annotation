<?php

/*
 * 绑定调用，主要用于业务服务处理中的特殊场景，比如服务事件、处理非正常情况
 */

namespace WorkermanAnnotation\Annotations;

/**
 * @DefineUse(function=true)
 * @DefineParam(name="name", type="string", default="") 指定绑定的别名（不指定为方便名），可指定为协议名：http、websocket用来处理协议异常，也可以指定为业务事件处理名：start、stop、connect、close。其它自定义别名等
 */
class BindCall implements iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $indexs = [];
        $name = $input['ref']->getName();
        foreach ($params as $param) {
            $indexs[] = $param['name'] ?: $name;
        }
        return [
            'bind-call' => $indexs
        ];
    }

}
