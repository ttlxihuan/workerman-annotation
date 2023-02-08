<?php

/*
 * 使用中间件注解处理
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\AnnotationHandle;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="name", type="string") 指定要使用的中间件名
 */
class UseWmiddleware implements iAnnotation {

    /**
     * @var Annotation 注解处理器
     */
    protected static $annotation;

    /**
     * 初始化处理
     */
    public function __construct() {
        if (static::$annotation) {
            return;
        }
        $config = config('annotation.middleware');
        if (is_array($config)) {
            static::$annotation = new AnnotationHandle(...$config);
        } else {
            throw new \Exception('请配置中间件注解信息');
        }
    }

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $callbacks = [];
        foreach ($params as $param) {
            $name = $param['name'];
            $callbacks[] = function($params, \Closure $next)use($name) {
                return static::$annotation->callIndex('middleware', $name, ...$params) ?: $next();
            };
        }
        return $callbacks;
    }

}
