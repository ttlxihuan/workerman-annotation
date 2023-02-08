<?php

/*
 * 请求处理扩展，方法请求数据处理
 */

namespace WorkermanAnnotation\Protocols\Http;

class Request extends \Workerman\Protocols\Http\Request implements \ArrayAccess {

    /**
     * 判断参数是否存在
     * @param type $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
        if (isset($this->_data['get'][$offset])) {
            return true;
        }
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            return isset($this->_data['post'][$offset]) || isset($this->_data['files'][$offset]);
        }
        return false;
    }

    /**
     * 获取参数
     * @param type $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            return $this->_data['post'][$offset] ?? $this->_data['files'][$offset] ?? $this->_data['get'][$offset];
        }
        return $this->_data['get'][$offset] ?? null;
    }

    /**
     * 设置参数
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void {
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            $this->_data['post'][$offset] = $value;
        }
        $this->_data['get'][$offset] = $value;
    }

    /**
     * 删除参数
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void {
        if (in_array($this->method(), ['POST', 'PUT'], true) && isset($this->_data['post'][$offset])) {
            unset($this->_data['post'][$offset]);
        } else {
            unset($this->_data['get'][$offset]);
        }
    }

}
