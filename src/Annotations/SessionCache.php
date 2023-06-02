<?php

/*
 * session缓存同步处理
 */

namespace WorkermanAnnotation\Annotations;

use Closure;
use WorkermanAnnotation\Cache;
use GatewayWorker\Lib\Context;

/**
 * @DefineUse(function=true, class=true)
 * @DefineParam(name="init", type="bool", default=false) 指定是否初始
 */
class SessionCache implements iAnnotation {

    /**
     * @var Config 本地session集
     */
    private static $sessions;

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $init = end($params)['init'];
        return [
            function (array $call_params, Closure $next, string $name)use ($init) {
                $client_id = Context::$client_id;
                if (empty($client_id)) {
                    return $next();
                }
                $session = $_SESSION;
                if ($init) {
                    static::$sessions[$client_id] = $_SESSION;
                } else {
                    if (empty(static::$sessions[$client_id])) {
                        // 取缓存
                        static::$sessions[$client_id] = unserialize((string) Cache::get($client_id . '-session')) ?: $_SESSION ?: [];
                    }
                    $_SESSION = static::$sessions[$client_id];
                }
                $result = $next();
                if ($_SESSION) {
                    $now = time();
                    // 更新要求处理
                    if (static::$sessions[$client_id] !== $_SESSION || empty($_SESSION['cache-time']) || $_SESSION['cache-time'] < $now - 3600) {
                        $_SESSION['cache-time'] = $now;
                        static::$sessions[$client_id] = $_SESSION;
                        Cache::set($client_id . '-session', serialize($_SESSION), 'EX', 86400);
                    }
                } else {
                    // 清除缓存
                    unset(static::$sessions[$client_id]);
                    Cache::del($client_id . '-session');
                }
                $_SESSION = $session;
                return $result;
            }
        ];
    }

}
