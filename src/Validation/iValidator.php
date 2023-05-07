<?php

/*
 * 验证接口
 */

namespace WorkermanAnnotation\Validation;

interface iValidator {

    /**
     * 通过验证处理，不通过将报异常
     * @param array|ArrayAccess $data
     * @param array $config
     * @return boolean
     * @throws BusinessException
     */
    public function adopt(&$data, array $config);
}
