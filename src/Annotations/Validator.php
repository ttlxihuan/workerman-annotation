<?php

/*
 * 验证注解处理
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\Validator as ValidatorRun;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="name", type="string")  验证字段名
 * @DefineParam(name="value", type="mixed") 默认值
 * @DefineParam(name="rules", type="string") 验证规则
 * @DefineParam(name="title", type="string", default="") 验证字段标题名，不指定则为字段名
 */
class Validator implements iAnnotation {

    /**
     * @var bool 事务操作处理器
     */
    private static $handle = null;

    /**
     * 初始化处理
     */
    public function __construct() {
        if (empty(static::$handle)) {
            static::addHandle(function (array $data, array $rules) {
                ValidatorRun::adopt($data, $rules);
            });
        }
    }

    /**
     * 设置缓存处理器
     * @param Closure $validator
     */
    public static function addHandle(\Closure $validator) {
        static::$handle = $validator;
    }

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        return [
            function (array &$call_params, \Closure $next)use ($params) {
                call_user_func(static::$handle, $call_params[0] ?? [], $params);
                return $next();
            }
        ];
    }

}
