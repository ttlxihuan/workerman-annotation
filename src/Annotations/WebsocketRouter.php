<?php

/*
 * Websocket协议注解
 * 定义为Websocket协议处理控制器
 * 需要为每个方法配置路由注解数据，可以通过定义的路由伪装代码结构，但会占用存储空间
 */

namespace WorkermanAnnotation\Annotations;

use GatewayWorker\Lib\Gateway;
use GatewayWorker\Lib\Context;
use GatewayWorker\BusinessWorker;
use WorkermanAnnotation\AnnotationHandle;
use WorkermanAnnotation\BusinessException;

/**
 * @DefineUse(class=true)
 * @DefineParam(name="path", type="string", default="") 指定路由前段路径
 * @DefineParam(name="route", type="string", default="type") 指定通信路由键名
 */
class WebsocketRouter implements iAnnotation {

    /**
     * @var array 路由记录
     */
    protected $routes = [];

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        if (!count($this->routes)) {
            $this->create($input['parse']);
        }
        $indexs = [];
        // 数据整理
        foreach ($params as $param) {
            $indexs[$param['path']] = $param['path'];
            $this->routes[$param['path']] = $param['route'];
        }
        return [
            'websocket' => $indexs,
        ];
    }

    /**
     * 创建路由处理器
     * @param Annotation $parse
     */
    protected function create(AnnotationHandle $parse) {
        $ping = workerConfig('server.gateway.ping.data');
        $parse->addCall(function (array $params)use ($parse, $ping) {
            $message = $params[0];
            if ($message === $ping) {
                return;
            }
            $format = $this->getFormat($message);
            $data = $this->decode($format, $message);
            if ($data === false) {
                $result = $parse->callIndex('bind-call', 'websocket', [], new BusinessException('消息解码错误'));
                goto RETURN_RESULT;
            }
            try {
                foreach ($this->routes as $path => $route) {
                    if (isset($data[$route]) && (empty($path) || strpos($data[$route], $path) === 0)) {
                        $result = $parse->callTillIndexs([
                            'websocket-router' => $data[$route],
                            'bind-call' => 'websocket',
                                ], $data);
                        goto RETURN_RESULT;
                    }
                }
                $result = $parse->callIndex('bind-call', 'websocket', $data, new BusinessException('找不到路由'));
            } catch (\Exception $err) {
                $result = $parse->callIndex('bind-call', 'websocket', $data, $err);
                if (!$err instanceof BusinessException) {
                    BusinessWorker::log('[ERROR] ' . $err->getMessage() . PHP_EOL . $err->getTraceAsString());
                }
            }
            RETURN_RESULT:
            if (is_array($result) || $result instanceof \ArrayAccess) {
                if (isset($data[$route])) {
                    $result[$route] = $data[$route];
                    return $this->encode($format, $result);
                }
                Gateway::closeClient(Context::$client_id);
            }
        });
    }

    /**
     * 获取数据格式类型
     * @param string $message
     * @return string
     */
    protected function getFormat(string $message): string {
        if (preg_match('/^\s*(\{.*\}|\[.*\])\s*$/', $message)) {
            return 'json';
        } elseif (preg_match('/^(<.*>)$/', $message)) {
            return 'xml';
        } else {
            return 'json';
        }
    }

    /**
     * 获取数据格式类型
     * @param string $message
     * @return array|false
     */
    protected function decode(string $format, $message) {
        switch ($format) {
            case 'json':
                $array = json_decode($message, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return false;
                }
                return $array;
            case 'xml':
                return $this->xmlToArray(simplexml_load_string($message, \SimpleXMLIterator::class));
        }
    }

    /**
     * 获取数据格式类型
     * @param string $message
     * @return string
     */
    protected function encode(string $format, $message): string {
        switch ($format) {
            case 'json':
                return json_encode($message);
            case 'xml':
                return $this->makeXml($message);
        }
    }

    /**
     * xml数据转数组
     * @param type $sxe
     * @return type
     */
    protected function xmlToArray($sxe) {
        $extract = [];
        foreach ($sxe->children() as $key => $value) {
            if (array_key_exists($key, $extract)) {
                if (!isset($extract[$key][0])) {
                    $tmp_extract = $extract[$key];
                    $extract[$key] = [];
                    $extract[$key][0] = $tmp_extract;
                } else
                    $extract[$key] = (array) $extract[$key];
            }
            if ($value->count()) {
                if (isset($extract[$key]) && is_array($extract[$key]))
                    $extract[$key][] = $this->extract($value);
                else
                    $extract[$key] = $this->extract($value);
            } else {
                if (isset($extract[$key]) && is_array($extract[$key]))
                    $extract[$key][] = empty(strval($value)) ? null : strval($value);
                else
                    $extract[$key] = empty(strval($value)) ? null : strval($value);
            }
        }
        return $extract;
    }

    /**
     * 生成XML
     * @param mixed $data
     * @return string
     */
    protected function makeXml($data): string {
        if (is_array($data) || $data instanceof \Iterator) {
            return '<?xml version="1.0" encoding="UTF-8"?>' . $this->arrayToXml($data);
        } else {
            return (string) $data;
        }
    }

    /**
     * 数组转xml数据
     * @param array|Iterator $data
     * @return string
     */
    protected function arrayToXml($data): string {
        $xml = '';
        foreach ($data as $name => $value) {
            if (is_array($value) || $value instanceof \Iterator) {
                $xml .= "<$name>" . $this->arrayToXml($value) . "</$name>";
            } else {
                $xml .= "<$name>$value</$name>";
            }
        }
        return $xml;
    }

}
