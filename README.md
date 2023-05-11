# workerman-annotation
基于GatewayWorker注解处理，通过注解解析绑定各模块调用，并且提供HTTP协议支持。
* 推荐通过 ttlphp/workerman-fast 进行快速使用。

目录
-----------------
* [安装](#installation)
* [使用](#use)
* [注意项](#attention)

Installation
------------
### 使用composer安装（推荐）
```shell
composer require ttlphp/workerman-annotation dev-main
```

### 使用GIT安装
使用GIT命令下载程序包。
```
git clone https://github.com/ttlxihuan/workerman-annotation
```
修改自动加载配置

打开 `vendor/composer/autoload_psr4.php` 添加命令空间加载目录：
```php
'WorkermanAnnotation\\' => array($vendorDir . '/workerman-annotation/src'),
```
打开 `vendor/composer/autoload_static.php` 添加命令空间加载目录，此文件有多处需要添加，暂不示例

打开 `vendor/composer/autoload_files.php` 添加助手函数加载：


use
------------
### 使用前准备
workerman-annotation是一个简单小巧的注解处理层，内部主要是注解处理部分，并附带了一些应用使用的简单功能。

#### 固定应用目录：
* app/Controllers/    控制器处理目录
* app/Middlewares/    中间件处理目录
* app/Timers/         定时器处理目录
* config/             配置文件目录
* provides/           三方扩展加载目录

### 如何使用
```php
require __DIR__.'/vendor/autoload.php';
// 启动服务
serverRun();
```

### 内置注解
内置注解主要用于完成基本服务运行，如果不能满足要求还可以自定义注解处理器。
** 注意：函数必需是非静态并且公有使用注解才会被解析生效 **

#### @Register(class=string)
内置固定注解处理器，注册要使用的注解，使用注解前必需进行注册，注册后的注解能向下延续（即子类中可以使用）。
* class  注解处理类名，内置注解不需要指定全类名（即不含命名空间）。

#### @DefineUse(function=bool, class=bool)
内置固定注解处理器，注解处理类专用注解，用来指定为注解处理类可使用位置
* function  在方法上使用，默认否
* class     在类上使用，默认否

#### @DefineParam(name=string, type=string, default=mixed)
内置固定注解处理器，注解处理类专用注解，用来指定注解处理类参数，多个参数需要使用多次此注解。
* name      参数名
* type      参数数据类型，可选值：bool、int、float、string、mixed。PHP8注解额外可使用：array、object
* default   参数默认值

#### @BindCall(name=string)
绑定调用注解，可以注册 bind-call.name 的索引调用，等待特殊调用。此注解主要用于服务路由不匹配或服务部分事件处理。
* name      绑定索引名，内置固定：websocket（路由找不到或处理报错）、http（路由找不到或处理报错）、start（服务启动）、stop（服务停止）、connect（客户连接）、close（客户断开）

#### @HttpRouter(path=string)
HTTP请求路由处理注册，指定后就可以在服务处理事件时调用路由，完成请求操作。如果是静态文件需要存放在 public/ 目录下。
* path      路由前缀，默认：/

#### @HttpMethod(type=string, name=string)
HTTP请求方法路由注册，指定后此方法就可以通过路由调用。
* type      请求类型，不指定为所有类型均可路由，多个使用逗号分开，可选：GET、POST、OPTIONS、HEAD、DELETE、PUT、PATCH
* name      路由后缀，不指定为方法名

#### @WebsocketRouter(path=string, route=string)
WebSocket请求路由处理注册，指定后就可以在服务处理事件时调用路由，完成请求操作。内置xml、json两种数据通信，会自动进行匹配，默认json。
* path      路由前缀，默认：空
* route     路由键名，从通信数据里提取，响应时会自动增加，默认：type

#### @WebsocketMethod(name=string)
WebSocket请求方法路由注册，指定后此方法就可以通过路由调用。
* name      路由后缀，不指定为方法名

#### @Middleware(name=string)
中间件注册，注册后可通过使用中间件注解进行绑定切入调用。
* name      中间件调用名

#### @UseWmiddleware(name=string)
使用中间件，指定后就可以绑定指定中间件处理器。
* name      中间件名

#### @Provide(action=string, name=string)
三方外部扩展包注解加载处理，使用外部扩展时可通过注解进行加载，同一类型扩展加载成功一个即停止加载其它相同扩展。
* action    扩展动作名，相同类型的扩展使用一样的名称
* name      扩展名，用来加载 /provides/name.php 文件的，此文件返回真就停止加载其它相同类型扩展文件

#### @Cache(timeout=int, name=string)
缓存函数返回值专用注解，此注解会截取函数返回值并进行缓存，下次调用时在缓存有效期内直接返回缓存值而不需要调用函数。
* timeout   指定缓存保存时长（秒），默认600秒。
* name      指定缓存处理名，用来选择不同的缓存，不指定则为配置默认连接。
* empty     是否缓存空值（以empty语句结果为准），默认不缓存空值。

#### @Transaction(name=string)
事务注解，可以函数调用时自动开启事务，当有报错时事务回滚否则事务提交。
* name      指定事务名，用来选择不同的数据库，不指定则为配置默认连接。

#### @Timer(id=int, interval=int, persistent=bool)
定时器注解，多进程时可以绑定指定进程号上运行，方便管理各定时器，如果只有一个进程运行时进程号无效。
* id        业务服务进程ID，<0时绑定在所有业务服务进程上，默认：0
* interval  定时调用间隔时长，默认：1
* persistent 是否循环定时器，默认：true
* basis     指定基准时间（H:i:s），用于按标准时间间隔定时处理
* worker    指定启动业务进程名，用于多业务进程名时划分处理

#### @Validator(name=string, value=mixed, rules=string, title=string)
验证参数注解，用来验证函数的第一个参数（必需是数组）。
* name      参数（数组）键名
* value     默认值
* rules     验证规则
* title     字段名，验证失败时提示用，不指定为 name

#### @Log(timeout=int)
调用超时日志记录
* timeout   调用处理超时时长（秒）

### 自定义注解
当内置注解不够用时可以自定义注解处理器。每个注解均有对应一个处理类，这个类必需继承接口 WorkermanAnnotation\Annotations\iAnnotation 。
通过DefineUse和DefineParam注解进行绑定参数和使用位置。

```php
// 示例
/**
 * @DefineUse(function=true)
 * @DefineParam(name="name", type="string", default="")
 */
class TextAnnotation implements \WorkermanAnnotation\Annotations\iAnnotation {

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        return [
            function($params, \Closure $next){ // 返回切入处理器
                // $params 是使用注解的函数参数
                return $next();
            },
            // 返回索引， 可以通过注解处理器索引调用注解函数
            'test' => 'test'
        ];
    }

}
```

attention
------------
ttlphp/workerman-fast 是 workerman-annotation 专用应用层，通过 ttlphp/workerman-fast 能实现快速运行使用 workerman-annotation。

