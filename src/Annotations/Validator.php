<?php

/*
 * 验证注解处理
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\Validation\iValidator;
use WorkermanAnnotation\Validation\Validator as ValidatorRun;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="name", type="string")  验证字段名
 * @DefineParam(name="value", type="mixed") 默认值
 * @DefineParam(name="rules", type="string") 验证规则
 * @DefineParam(name="title", type="string", default="") 验证字段标题名，不指定则为字段名
 */
class Validator implements iAnnotation {

    /**
     * @var iValidator 验证处理器
     */
    private static $validator = null;

    /**
     * 初始化处理
     */
    public function __construct() {
        if (empty(static::$validator)) {
            static::addHandle(new ValidatorRun());
        }
    }

    /**
     * 设置缓存处理器
     * @param iValidator $validator
     */
    public static function addHandle(iValidator $validator) {
        static::$validator = $validator;
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
                static::$validator->adopt($call_params[0], $params);
                return $next();
            }
        ];
    }

}
