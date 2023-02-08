<?php

/*
 * 注解应用时处理接口
 */

namespace WorkermanAnnotation;

use Reflector;

interface iAnnotation {

    /**
     * 解析注解数据
     * @param Reflector $ref
     * @param array $params
     * @return array
     */
    public function parse(\Reflector $ref, array $params): array;

    /**
     * 应用运行注解处理
     * @param array $params
     * @param array $input
     * @return mixed
     */
    public function run(array $params, array $input);
}
