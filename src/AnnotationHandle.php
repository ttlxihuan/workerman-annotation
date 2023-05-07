<?php

/*
 * 注解处理类
 * 注解使用：
 *  1、注册注解处理类
 *  2、定义注解处理类参数
 *  3、使用注解并指定参数
 * 
 * 注解注册应该放在根类上，注解注册是唯一的（注解解析实例内）
 * 注解使用时子类会重写父类的注解参数，并渗透到子类方法中
 * 
 * 所有注解使用前必需进行注册，注解解析器中已经预注册了几个基础注解处理类，通过这几个注解进行注册注解操作。
 * 预注册注解
 *   注册使用注解
 *      @Register(class="")
 *          注册一个注解处理类，此类必需实现接口 WorkermanAnnotation\Annotations\iAnnotation ，注解处理类需要处理解析出来的注解数据，生成调用处理功能。
 *              class   注册处理类名，需要全类名，否则加载失败会报错
 * 
 *   定义注解处理器
 *      @DefineUse(function=false, class=false)
 *          注解处理类专用注解，所有要注册的注解处理类均需要进行指定注解的使用范围。
 *              function    指定注解是否可以方法上使用，默认否
 *              class       指定注解是否可以类上使用，默认否
 * 
 *      @DefineParam(name="", type="", default="")
 *          注解处理类专用注解，当注册的注解处理类需要参数时则通过此注解进行指定。多个参数时需要多次使用此注解
 *              name        注解参数名
 *              type        注解参数类型，不指定为 mixed，暂时只支持：string、int、float、bool、mixed
 *              default     注解参数默认值
 * 
 * 注解处理器处理均需要返回一个数组，在不同的注解类型时数组作用不一。
 *      通用使用类注解
 *          [callback, callback, index => name]   callback 为渗透到各方法中的切入函数，index 是送入方法注解提取合并使用
 * 
 *      通用使用方法注解
 *          [callback, callback, index => name]   callback 为方法的切入函数，index 是方法索引调用路径
 * 
 *      内部预注册注解
 *          [name => array ...]   name 是注解使用名，array 是预处理注解数据体
 * 
 */

namespace WorkermanAnnotation;

use Exception;
use Reflector;
use ReflectionClass;
use ReflectionMethod;
use WorkermanAnnotation\Annotations\iAnnotation;

class AnnotationHandle {

    /**
     * @var array 回调集合，通过方法名调用
     */
    protected $callbacks = [];

    /**
     * @var array 索引集合，通过索引调用
     */
    protected $indexes = [];

    /**
     * 初始化
     * @param string $baseClass
     * @param string $baseNamespace
     * @param string $path
     */
    public function __construct(string $baseClass, string $baseNamespace = null, string $path = null) {
        if (is_null($path)) {
            $array = explode('\\', $baseClass);
            $childPath = end($array) . 's';
            $path = __DIR__ . '/' . $childPath;
            $baseNamespace = preg_replace('#[/\\\\][^/\\\\]+$#', '', $baseClass) . '\\' . $childPath;
        }
        $this->load($baseClass, $baseNamespace, $path);
    }

    /**
     * 获取默认注册注解应用处理器
     * @return array
     */
    protected function getDefaultRegisterUses(): array {
        return [
            // @Register(name="timer", class="")
            'Register' => [
                'class' => true,
                'params' => [
                    'class' => ['type' => 'string'],
                ],
                'instance' => new Annotations\Register(),
            ],
        ];
    }

    /**
     * 获取默认定义注解应用处理器
     * @return array
     */
    protected function getDefaultDefineUses(): array {
        return [
            // @DefineUse(function=true, class=true, key="name")
            'DefineUse' => [
                'class' => true,
                'params' => [
                    'function' => ['type' => 'bool', 'default' => false],
                    'class' => ['type' => 'bool', 'default' => false],
                ],
                'instance' => new Annotations\DefineUse()
            ],
            // @DefineParam(name="", type="", default="")
            'DefineParam' => [
                'class' => true,
                'params' => [
                    'name' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'default' => 'mixed'],
                    'default' => ['type' => 'mixed'],
                ],
                'instance' => new Annotations\DefineParam()
            ],
        ];
    }

    /**
     * 加载注解
     * @param string $baseClass
     * @param string $baseNamespace
     * @param string $path
     * @throws Exception
     */
    protected function load(string $baseClass, string $baseNamespace, string $path) {
        if (!class_exists($baseClass)) {
            throw new Exception('要加载的注解基类 ' . $baseClass . ' 不存在');
        }
        $baseNamespace = rtrim($baseNamespace, '\\') . '\\';
        if (is_dir($path) && file_exists($path) && is_readable($path)) {
            $array = explode('\\', $baseClass);
            $suffix = end($array);
            $children = [];
            $basePath = realpath($path);
            foreach (glob("{$basePath}/*{$suffix}.php") as $file) {
                $childClass = rtrim($baseNamespace . str_replace([$basePath, '/'], ['', '\\'], dirname($file)), '\\') . '\\' . basename($file, '.php');
                // 跳过基类自己
                if (trim($childClass, '\\') === trim($baseClass, '\\')) {
                    continue;
                }
                if (class_exists($childClass) && is_subclass_of($childClass, $baseClass)) {
                    $ref = new ReflectionClass($childClass);
                    if (!$ref->isTrait() && !$ref->isInterface()) {
                        $children[$childClass] = $ref;
                    }
                } else {
                    throw new Exception('要加载的注解类 ' . $childClass . ' 不存在或不继承基类： ' . $baseClass);
                }
            }
            $this->extractParentClass(new ReflectionClass($baseClass), $children);
        } else {
            throw new Exception('要加载的注解目录 ' . $path . ' 不能正常访问');
        }
    }

    /**
     * 从父类开始向下提取注解数据
     * @param ReflectionClass $baseRef
     * @param array $childrenRef
     * @param array $uses
     * @param array $base
     * @return int
     */
    protected function extractParentClass(ReflectionClass $baseRef, array &$childrenRef, array $uses = [], array $base = []): int {
        $items = $this->apply($baseRef, $uses);
        $data = array_merge($base, $this->callMake($uses, $items, ['ref' => $baseRef, 'parse' => $this]));
        $count = 0;
        foreach ($childrenRef as $index => $ref) {
            if (!is_subclass_of($ref->getName(), $baseRef->getName())) {
                continue;
            }
            // 提取相近的父类进行解析
            $parent = $ref->getParentClass();
            $existParent = null;
            if ($parent->getName() != $baseRef->getName()) {
                do {
                    // 提取存在解析的上级
                    $existParent = $childrenRef[$parent->getName()] ?? $existParent;
                } while ($parent = $parent->getParentClass());
                if ($existParent) {
                    unset($childrenRef[$existParent->getName()]);
                    $this->extractParentClass($existParent, $childrenRef, $uses, $data);
                    continue;
                }
            }
            // 解析库中没有下级
            if ($ref->isInstantiable() && !$this->extractParentClass($ref, $childrenRef, $uses)) {
                unset($childrenRef[$index]);
                $this->extractClass($ref, $uses, $data);
                $count++;
            }
        }
        return $count;
    }

    /**
     * 从类开始向下提取注解数据
     * @param ReflectionClass $class
     * @param array $uses
     * @param array $data
     */
    protected function extractClass(ReflectionClass $class, array $uses = [], array $data = []) {
        $object = $class->newInstance();
        // 剥离数据，索引类、回调类
        $callbacks = [];
        $indexes = [];
        $items = $this->apply($class, $uses);
        foreach (array_merge($data, $this->callMake($uses, $items, ['ref' => $class, 'parse' => $this])) as $index => $item) {
            if ($item instanceof \Closure) {
                $callbacks[] = $item;
            } else {
                $indexes[$index] = $item;
            }
        }
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
            if ($refMethod->isConstructor() || $refMethod->isDestructor() || $refMethod->isStatic()) {
                continue;
            }
            $name = $this->getRefName($refMethod);
            foreach ($callbacks as $callback) {
                $this->callbacks[$name][] = $callback;
            }
            $this->extractFunction($object, $refMethod, $uses, $indexes);
        }
    }

    /**
     * 从方法中提取注解数据
     * @param object $object
     * @param ReflectionMethod $refMethod
     * @param array $uses
     * @param array $indexes
     */
    protected function extractFunction(object $object, ReflectionMethod $refMethod, array $uses = [], array $indexes = []) {
        $name = $this->getRefName($refMethod);
        foreach ($this->callMake($uses, $this->apply($refMethod, $uses), ['indexs' => $indexes, 'ref' => $refMethod, 'parse' => $this]) as $index => $item) {
            if ($item instanceof \Closure) {
                $this->callbacks[$name][] = $item;
            } else {
                foreach ($item as $path) {
                    $this->indexes[$index][$path][] = $name;
                }
            }
        }
        $method = $refMethod->getName();
        $this->callbacks[$name][] = function (array $params)use ($object, $method) {
            return $object->$method(...$params);
        };
    }

    /**
     * 注解应用处理
     * @param Reflector $ref
     * @param array $uses
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function apply(Reflector $ref, array &$uses): array {
        static $registerUses = null;
        if (empty($registerUses)) {
            $registerUses = $this->getDefaultRegisterUses();
        }
        // 注解定义位置名
        $annotations = $this->parse($ref);
        // 注册定义处理
        $registers = $this->convert($ref, $annotations, $registerUses);
        try {
            // 执行生成注解定义数据，合并注解应用处理
            foreach ($this->callMake($registerUses, $registers, ['parse' => $this]) as $name => $params) {
                if (isset($registerUses[$name]) || isset($uses[$name])) {
                    throw new Exception("注册注解名 $name 已经占用");
                }
                $uses[$name] = $params;
            }
        } catch (Exception $err) {
            throw new Exception($this->getRefName($ref) . ' ' . $err->getMessage());
        }
        // 常规注解应用处理类
        $items = $this->convert($ref, $annotations, $uses);
        if (count($annotations)) {
            throw new Exception($this->getRefName($ref) . " 未知注解 " . implode('、', array_keys($annotations)));
        }
        return $items;
    }

    /**
     * 解析注解处理类定义信息
     * @staticvar type $defineUses
     * @param string $class
     * @return array
     * @throws Exception
     */
    public function parseDefine(string $class): array {
        static $defineUses = null;
        if (empty($defineUses)) {
            $defineUses = $this->getDefaultDefineUses();
        }
        if (class_exists($class)) {
            if (!is_subclass_of($class, iAnnotation::class)) {
                throw new Exception("注册注解处理类 $class 未继承注解处理接口 " . iAnnotation::class);
            }
            $ref = new ReflectionClass($class);
            $annotations = $this->parse($ref);
            $data = $this->callMake($defineUses, $this->convert($ref, $annotations, $defineUses));
            if (count($annotations)) {
                throw new Exception('注解处理类 ' . $this->getRefName($ref) . " 使用未知注解 " . implode('、', array_keys($annotations)));
            }
            return $data;
        } else {
            throw new Exception("注册注解处理类 $class 不存在");
        }
    }

    /**
     * 按定义注解信息转换注解数据
     * @param Reflector $ref
     * @param array $annotations
     * @param array $defines
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function convert(Reflector $ref, array &$annotations, array $defines): array {
        $data = [];
        // 注册定义应用处理
        foreach ($defines as $name => $define) {
            if (empty($annotations[$name])) {
                continue;
            }
            if (empty($define['class']) && $ref instanceof ReflectionClass) {
                throw new Exception($this->getRefName($ref) . " 指定了不可在定义类的位置使用的注解 $name");
            }
            if (empty($define['function']) && $ref instanceof ReflectionMethod) {
                throw new Exception($this->getRefName($ref) . " 指定了不可在定义方法的位置使用的注解 $name");
            }
            // 注解应用处理
            if (empty($define['instance']) || !is_object($define['instance'])) {
                throw new Exception($this->getRefName($ref) . " 注解 $name 应用处理类无处理实例");
            }
            $define_params = $define['params'] ?: [];
            foreach ($annotations[$name] as $params) {
                $parameters = [];
                foreach ($define_params as $attrname => $param) {
                    if (isset($params[$attrname])) {
                        $value = $params[$attrname];
                        unset($params[$attrname]);
                    } else {
                        $value = $param['default'] ?? null;
                        if ($param['type'] !== 'mixed' && is_null($value)) {
                            throw new Exception($this->getRefName($ref) . " 注解 $name 必需指定属性 $attrname");
                        }
                    }
                    if ($this->checkDataType($value, $param['type'])) {
                        $parameters[$attrname] = $value;
                    } else {
                        throw new Exception($this->getRefName($ref) . " 注解 $name 属性 $attrname 数据类型必需是 {$param['type']}");
                    }
                }
                if (count($params)) {
                    throw new Exception($this->getRefName($ref) . " 注解 $name 指定了未知属性 " . implode('、', array_keys($params)));
                }
                $data[$name][] = $parameters;
            }
            unset($annotations[$name]);
        }
        return $data;
    }

    /**
     * 验证数据类型
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    protected function checkDataType($value, string $type): bool {
        if ($type === 'mixed') {
            return true;
        }
        switch (gettype($value)) {
            case 'boolean':
                return $type === 'bool';
            case 'integer':
                return $type === 'int';
            case 'double':
                return $type === 'float';
            case 'string':
                return $type === 'string';
            case 'array':
                return $type === 'array';
            case 'object':
                return $type === 'object';
            case 'NULL':
                return $type === 'null';
        }
        return false;
    }

    /**
     * 注解数据生成处理调用
     * @param array $defines
     * @param array $params
     * @param array $input
     * @return array
     */
    protected function callMake(array $defines, array $params, array $input = []): array {
        $result = [];
        foreach ($defines as $name => $define) {
            if (empty($params[$name])) {
                continue;
            }
            $result = array_merge($result, $define['instance']->make($params[$name], $input));
        }
        return $result;
    }

    /**
     * 添加注解调用
     * @param \Closure $callback
     * @param Reflector $ref
     */
    public function addCall(\Closure $callback, Reflector $ref = null) {
        $this->callbacks[$ref ? $this->getRefName($ref) : '@'][] = $callback;
    }

    /**
     * 添加注解索引
     * @param string $name
     * @param string $index
     * @param Reflector $ref
     */
    public function addCallIndex(string $name, string $index, Reflector $ref = null) {
        $this->indexes[$name][$index][] = $ref ? $this->getRefName($ref) : '@';
    }

    /**
     * 调用注解函数
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function call(string $name, ...$params) {
        $callbacks = $this->callbacks[$name] ?? [];
        $next = function()use(&$callbacks, &$next, &$params, $name) {
            if ($callback = array_shift($callbacks)) {
                return $callback($next, $params, $name);
            }
        };
        return $next();
    }

    /**
     * 通过注解索引调用注解函数
     * @param string $name
     * @param string $index
     * @param array $params
     * @return mixed
     */
    public function callIndex(string $name, string $index, ...$params) {
        foreach ($this->indexes[$name][$index] ?? [] as $method) {
            if (is_null($result = $this->call($method, ...$params))) {
                continue;
            }
            return $result;
        }
    }

    /**
     * 通过多个可选索引进行调用，直到某个索引存在则不再向下调用。
     * @param array $indexs
     * @param array $params
     * @return mixed
     */
    public function callTillIndexs(array $indexs, ...$params) {
        foreach ($indexs as $name => $items) {
            foreach (is_array($items) ? $items : [$items] as $index) {
                if ($this->hasCallIndex($name, $index)) {
                    return $this->callIndex($name, $index, ...$params);
                }
            }
        }
    }

    /**
     * 判断是否存在调用
     * @param string $name
     * @return bool
     */
    public function hasCall(string $name): bool {
        return isset($this->callbacks[$name]);
    }

    /**
     * 判断是否存在索引
     * @param string $name
     * @param string $index
     * @return bool
     */
    public function hasCallIndex(string $name, string $index): bool {
        return isset($this->indexes[$name][$index]);
    }

    /**
     * 解析注解内容
     * @param Reflector $ref
     * @return array
     */
    protected function parse(Reflector $ref): array {
        $doc = $ref->getDocComment();
        $array = [];
        $nameReg = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
        $valueReg = '([\+\-]?\d+(\.\d+)?|true|false|null|"([^"]+|\\\\\.)*"|\'[^\']*\')';
        if (preg_match_all("/@{$nameReg}\s*\(\s*({$nameReg}\s*=\s*{$valueReg}\s*(,\s*{$nameReg}\s*=\s*{$valueReg}\s*)*)?\)/i", $doc, $matches)) {
            foreach ($matches[0] as $item) {
                preg_match("/^@({$nameReg})\s*\(/i", $item, $names);
                preg_match_all("/({$nameReg})\s*=\s*({$valueReg})/i", $item, $params);
                $name = $names[1];
                $items = [];
                foreach ($params[1] as $key => $param) {
                    $value = $params[2][$key];
                    switch ($value[0]) {
                        case '"':
                            $value = preg_replace('/\\\\(.)/', '$1', $params[2][$key]);
                        case "'":
                            $value = substr($value, 1, -1);
                            break;
                        default:
                            switch ($value) {
                                case 'true':
                                    $value = true;
                                    break;
                                case 'false':
                                    $value = false;
                                    break;
                                case 'null':
                                    $value = null;
                                    break;
                                default:
                                    if (strpos($value, '.') === false) {
                                        $value = intval($value);
                                    } else {
                                        $value = floatval($value);
                                    }
                                    break;
                            }
                    }
                    $items[$param] = $value;
                }
                $array[$name][] = $items;
            }
        }
        // 核验处理，注解语法错误将终止程序
        if (preg_match_all("/@{$nameReg}\s*\(.*?\)/i", $doc, $tags) && count($tags[0]) != count($matches[0])) {
            $data = $matches[0] ?? [];
            foreach ($tags[0] as $tag) {
                foreach ($data as $key => $item) {
                    if (strpos($item, $tag) === 0) {
                        unset($data[$key]);
                        continue 2;
                    }
                }
                throw new Exception($this->getRefName($ref) . " 注解 $tag 语法错误");
            }
        }
        // 兼容PHP8+注解
        if (method_exists($ref, 'getAttributes')) {
            foreach ($ref->getAttributes() as $attribute) {
                $array[$attribute->getName()][] = $attribute->getArguments();
            }
        }
        return $array;
    }

    /**
     * 获取反射名称
     * @param Reflector $ref
     * @return string
     */
    public function getRefName(Reflector $ref): string {
        if ($ref instanceof ReflectionClass) {
            return $ref->getName();
        } elseif ($ref instanceof ReflectionMethod) {
            return $ref->getDeclaringClass()->getName() . '::' . $ref->getName();
        } else {
            throw new Exception('暂不支持映射类 ' . get_class($ref));
        }
    }

}
