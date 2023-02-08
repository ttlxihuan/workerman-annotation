<?php

/*
 * 注解应用处理接口
 */

namespace WorkermanAnnotation\Annotations;

interface iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array;
}
