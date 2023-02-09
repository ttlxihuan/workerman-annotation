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
        return $this[$offset] !== null;
    }

    /**
     * 获取参数
     * @param type $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            return $this->post($offset) ?? $this->file($offset) ?? $this->get($offset);
        }
        return $this->get($offset);
    }

    /**
     * 设置参数
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void {
        if (in_array($this->method(), ['POST', 'PUT'], true)) {
            $this->post();
            $this->_data['post'][$offset] = $value;
        } else {
            $this->get();
            $this->_data['get'][$offset] = $value;
        }
    }

    /**
     * 删除参数
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void {
        if (in_array($this->method(), ['POST', 'PUT'], true) && isset($this[$offset])) {
            unset($this->_data['post'][$offset], $this->_data['files'][$offset]);
        } else {
            unset($this->_data['get'][$offset]);
        }
    }

}
