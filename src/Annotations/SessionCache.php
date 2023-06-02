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
 * @DefineParam(name="type", type="string", default='use') 指定缓存操作类型，仅支持：init、clean、use
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
        $type = end($params)['type'];
        return [
            function (array $call_params, Closure $next, string $name)use ($type) {
                $client_id = Context::$client_id;
                if (empty($client_id) || !Cache::valid()) {
                    return $next();
                }
                $session = $_SESSION;
                switch ($type) {
                    case 'init':
                        static::$sessions[$client_id] = [];
                        break;
                    case 'clean':
                        static::$sessions[$client_id] = [];
                        $_SESSION = [];
                        break;
                    case 'use':
                    default:
                        if (empty(static::$sessions[$client_id])) {
                            // 取缓存
                            static::$sessions[$client_id] = unserialize((string) Cache::get($client_id . '-session')) ?: $_SESSION ?: [];
                        }
                        $_SESSION = static::$sessions[$client_id];
                        break;
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
