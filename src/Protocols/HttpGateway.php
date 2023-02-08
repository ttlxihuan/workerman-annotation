<?php

/*
 * Http协议网关处理
 */

namespace WorkermanAnnotation\Protocols;

use Workerman\Connection\TcpConnection;

class HttpGateway extends \Workerman\Protocols\Http {

    /**
     * Http encode.
     *
     * @param string|Response $response
     * @param TcpConnection $connection
     * @return string
     */
    public static function encode($response, TcpConnection $connection) {
        return $response;
    }

    /**
     * Http decode.
     *
     * @param string $recv_buffer
     * @param TcpConnection $connection
     * @return \Workerman\Protocols\Http\Request
     */
    public static function decode($recv_buffer, TcpConnection $connection) {
        return $recv_buffer;
    }

}
