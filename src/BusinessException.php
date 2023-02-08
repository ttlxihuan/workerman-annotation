<?php

/*
 * 业务异常
 */

namespace WorkermanAnnotation;

class BusinessException extends \Exception {

    /**
     * @var string 异常状态码值
     */
    protected $codeValue;

    /**
     * 创建异常
     * @param string $message
     * @param string $codeValue
     * @param \Throwable $previous
     * @return \Exception
     */
    public function __construct(string $message, string $codeValue = 'FAIL', \Throwable $previous = NULL) {
        parent::__construct($message, 0, $previous);
        $this->codeValue = $codeValue;
    }

    /**
     * 获取异常状态码值
     * @return string|null
     */
    public function getCodeValue() {
        return $this->codeValue;
    }

}
