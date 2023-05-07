<?php

/*
 * 验证处理
 */

namespace WorkermanAnnotation\Validation;

use Closure;

class Validator extends iValidator {

    /**
     * @var array 验证规则说明
     */
    const RULE_MESSAGES = [
        'int' => [
            '*' => '{title}必需是有效整数',
            'min' => '最小数是：{min}',
            'max' => '最大数是：{max}',
        ],
        'float' => [
            '*' => '{title}必需是有效浮点数',
            'min' => '最小数是：{min}',
            'max' => '最大数是：{max}',
        ],
        'array' => [
            '*' => '{title}必需是有效数组',
            'min' => '最小个数是：{min}',
            'max' => '最大个数是：{max}',
        ],
        'string' => [
            '*' => '{title}必需是有效字符串',
            'min' => '最小长度是：{min}',
            'max' => '最大长度是：{max}',
        ],
        'phone' => [
            '*' => '{title}必需是有效手机号'
        ],
        'required' => [
            '*' => '{title}不能为空',
            'mutex' => '当{mutex}为空时',
            'rely' => '当{rely}不为空时',
        ],
        'in' => [
            '*' => '{title}可选值：{values}'
        ],
        'email' => [
            '*' => '{title}必需是有效邮箱'
        ],
        'date' => [
            '*' => '{title}必需是有效时间',
            'format' => '格式必需是：'
        ],
        'ip' => [
            '*' => '{title}必需是有效IP{type}地址',
        ],
        'url' => [
            '*' => '{title}必需是有效URL地址',
        ],
        'confirmed' => [
            '*' => '{title}必需与{target}相同',
        ],
        'file' => [
            '*' => '{title}必需上传文件',
        ]
    ];

    /**
     * 通过验证处理，不通过将报异常
     * @param array|ArrayAccess $data
     * @param array $config
     * @return boolean
     * @throws BusinessException
     */
    public function adopt(&$data, array $config) {
        $titles = array_column($config, 'title', 'name');
        foreach ($config as $item) {
            if (empty($item['name']) || empty($item['rules'])) {
                continue;
            }
            $get = function ($key = null, $isTitle = false)use (&$data, $item, $titles) {
                $key = $key ?: $item['name'];
                if ($isTitle) {
                    return $titles[$key] ?? $key;
                }
                if (isset($data[$key])) {
                    return $data[$key];
                } else {
                    $value = $item['value'] ?? null;
                    if (!is_null($value)) { // 写默认值
                        $data[$key] = $value;
                    }
                    return $value;
                }
            };
            $value = $get();
            foreach (explode('|', $item['rules']) as $rule) {
                $pos = strpos($rule, ':');
                if ($pos > 0) {
                    $name = strstr($rule, ':', true);
                    $params = explode(',', substr($rule, $pos + 1));
                } else {
                    $name = $rule;
                    $params = [];
                }
                if (strcasecmp($name, 'required') == 0 || strcasecmp($name, 'confirmed') == 0) {
                    array_unshift($params, $get);
                }
                array_unshift($params, $value);
                $method = "check$name";
                if (method_exists($this, $method) && is_array($result = $this->$method(...$params))) {
                    $this->makeErrorMsg($name, $get(null, true), $result);
                }
            }
        }
        return true;
    }

    /**
     * 验证为整数，且可大小指定范围
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return array|void
     */
    public function checkInt($value, int $min = null, int $max = null) {
        if (is_null($value)) {
            return;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) || is_numeric($value) && intval($value) == $value) {
            if (($min == null || $value >= $min) && ($max == null || $value <= $max)) {
                return;
            }
            return compact('min', 'max');
        } else {
            return [];
        }
    }

    /**
     * 验证为浮点数，且可大小指定范围
     * @param mixed $value
     * @param float $min
     * @param float $max
     * @return array|void
     */
    public function checkFloat($value, float $min = null, float $max = null) {
        if (is_null($value)) {
            return;
        }
        if (filter_var($value, FILTER_VALIDATE_FLOAT) || is_numeric($value) && floatval($value) == $value) {
            if (($min == null || $value >= $min) && ($max == null || $value <= $max)) {
                return;
            }
            return compact('min', 'max');
        } else {
            return [];
        }
    }

    /**
     * 验证为数组，且可以指定数组长度范围
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return array|void
     */
    public function checkArray($value, int $min = null, int $max = null) {
        if (is_null($value)) {
            return;
        }
        if (is_array($value)) {
            if (($min == null || count($value) >= $min) && ($max == null || count($value) <= $max)) {
                return;
            }
            return compact('min', 'max');
        } else {
            return [];
        }
    }

    /**
     * 验证为字符串，且可以指定字符串长度范围
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @return array|void
     */
    public function checkString($value, int $min = null, int $max = null) {
        if (is_null($value)) {
            return;
        }
        if (is_string($value)) {
            if (($min == null || strlen($value) >= $min) && ($max == null || strlen($value) <= $max)) {
                return;
            }
            return compact('min', 'max');
        } else {
            return [];
        }
    }

    /**
     * 验证为手机号
     * @param mixed $value
     * @return array|void
     */
    public function checkPhone($value) {
        if (is_null($value) || is_string($value) && preg_match('/^1[3-9]\d{9}$/', $value)) {
            return;
        }
        return [];
    }

    /**
     * 验证为时间
     * @param mixed $value
     * @param string $formats
     * @return array|void
     */
    public function checkDate($value, string ...$formats) {
        if (is_null($value)) {
            return;
        }
        if (is_string($value)) {
            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return;
                }
            }
            if (count($formats) == 0 && strtotime($value) !== false) {
                return;
            }
        }
        return count($formats) ? ['formats' => implode(',', $formats)] : [];
    }

    /**
     * 验证为邮箱
     * @param mixed $value
     * @return array|void
     */
    public function checkEmail($value) {
        if (is_null($value) || is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        return [];
    }

    /**
     * 验证为url地址
     * @param mixed $value
     * @return array|void
     */
    public function checkUrl($value) {
        if (is_null($value) || is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }
        return [];
    }

    /**
     * 验证为IP
     * @param mixed $value
     * @param string $type
     * @return array|void
     */
    public function checkIp($value, string $type = null) {
        if (is_null($value)) {
            return;
        }
        $params = [$value, FILTER_VALIDATE_IP];
        if ($type == '4') {
            $params[] = FILTER_FLAG_IPV4;
        } elseif ($type == '6') {
            $params[] = FILTER_FLAG_IPV6;
        } else {
            $type = '';
        }
        if (is_string($value) && filter_var(...$params)) {
            return;
        }
        return ['type' => $type];
    }

    /**
     * 验证是否为指定项
     * @param mixed $value
     * @param mixed $options
     * @return array|void
     */
    public function checkIn($value, ...$options) {
        if (is_null($value) || in_array($value, $options, true)) {
            return;
        }
        return ['values' => implode(',', $options)];
    }

    /**
     * 验证是否为上传文件
     * @param mixed $value
     * @return array|void
     */
    public function checkFile($value) {
        if (is_null($value) && isset($value['tmp_name']) && file_exists($value['tmp_name'])) {
            return;
        }
        return [];
    }

    /**
     * 验证为确认输入
     * @param mixed $value
     * @param Closure $get
     * @param mixed $options
     * @return array|void
     */
    public function checkConfirmed($value, Closure $get, string ...$options) {
        if (is_null($value)) {
            return;
        }
        foreach ($options as $name) {
            if ($value !== $get($name)) {
                return ['target' => $get($name, true)];
            }
        }
    }

    /**
     * 验证为必填
     * @param mixed $value
     * @param Closure $get
     * @param mixed $options
     * @return array|void
     */
    public function checkRequired($value, Closure $get, string ...$options) {
        $result = $this->hasValue($value);
        foreach ($options as $key) {
            if ($key[0] == '!') {
                $name = substr($key, 1);
                if (!$result && !$this->hasValue($get($name))) {
                    return ['mutex' => $get($name, true)];
                }
            } else {
                $name = $key;
                if (!$result && $this->hasValue($get($name))) {
                    return ['rely' => $get($name, true)];
                }
            }
        }
        if (count($options) == 0 && !$result) {
            return [];
        }
    }

    /**
     * 生成错误消息
     * @param string $rule
     * @param string $title
     * @param array $options
     * @throws BusinessException
     */
    protected function makeErrorMsg(string $rule, string $title, array $options) {
        $msgs = static::RULE_MESSAGES[$rule];
        $msg = str_replace('{title}', $title, $msgs['*']);
        foreach ($options as $name => $value) {
            if (!is_null($value)) {
                $msg = str_replace('{' . $name . '}', $value, $msg . (isset($msgs[$name]) ? '，' . $msgs[$name] : ''));
            }
        }
        throw new BusinessException($msg);
    }

    /**
     * 判断是否存在值
     * @param mixed $value
     * @return boolean
     */
    protected static function hasValue($value) {
        switch (gettype($value)) {
            case 'integer':
            case 'double':
            case 'boolean':
                return true;
            case 'string':
                return $value !== '';
            case 'array':
                return $value !== [];
            case 'object':
                return json_encode($value) !== '{}';
            case 'NULL':
                return false;
            default:
                return !!$value;
        }
    }

}
