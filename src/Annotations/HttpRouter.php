<?php

/*
 * http协议注解
 * 定义为http协议处理控制器
 * 需要为每个方法配置路由注解数据，可以通过定义的路由伪装代码结构，但会占用存储空间
 */

namespace WorkermanAnnotation\Annotations;

use WorkermanAnnotation\Event;
use Workerman\Protocols\Http;
use GatewayWorker\Lib\Context;
use GatewayWorker\Lib\Gateway;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;
use WorkermanAnnotation\AnnotationHandle;
use WorkermanAnnotation\BusinessException;
use WorkermanAnnotation\Protocols\Http\Request;

/**
 * @DefineUse(class=true)
 * @DefineParam(name="path", type="string", default="") 指定路由前段路径
 */
class HttpRouter implements iAnnotation {

    /**
     * @var array 静态文件类型集合
     */
    protected $mimeTypes = [];

    /**
     * 初始化处理
     */
    public function __construct() {
        Http::requestClass(Request::class);
        $this->loadMimeTypes(workerConfig('server.mime', __DIR__ . '/../../mime.types'));
    }

    /**
     * 获取连接处理器
     * @return TcpConnection|null
     */
    protected function getTcpConnection() {
        $address_data = Context::clientIdToAddress(Context::$client_id);
        if ($address_data) {
            $address = long2ip($address_data['local_ip']) . ":{$address_data['local_port']}";
            return Event::$businessWorker->gatewayConnections[$address];
        }
    }

    /**
     * 加载静态文件类型数据
     * @param string $file
     */
    protected function loadMimeTypes(string $file) {
        if (file_exists($file) && is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $data = preg_split('/\s+/', $line);
                if (count($data)) {
                    $type = array_shift($data);
                    foreach ($data as $ext) {
                        $this->mimeTypes[strtolower($ext)] = $type;
                    }
                }
            }
        }
    }

    /**
     * 请求静态文件
     * @param Request $request
     * @return Response
     */
    protected function requestStatic(Request $request) {
        $file = BASE_PATH . '/public/' . $request->path();
        // 取出文件名后缀
        $filename = basename($file);
        $index = strrpos($filename, '.');
        if ($index === false) {
            return;
        }
        $ext = strtolower(substr($filename, $index + 1));
        if (file_exists($file) && isset($this->mimeTypes[$ext])) {
            $result = new Response(200, ['Content-Type' => $this->mimeTypes[$ext]]);
            $result->file = [
                'file' => $file,
                'offset' => 0,
                'length' => 0,
            ];
            return $result;
        }
    }

    /**
     * 发送文件
     * @param Response $response
     */
    protected function sendFile(Response $response) {
        $file = $response->file['file'];
        $offset = $response->file['offset'];
        $length = $response->file['length'];
        clearstatcache();
        $file_size = (int) \filesize($file);
        $body_len = $length > 0 ? $length : $file_size - $offset;
        $response->withHeaders(array(
            'Content-Length' => $body_len,
            'Accept-Ranges' => 'bytes',
        ));
        if ($offset || $length) {
            $offset_end = $offset + $body_len - 1;
            $response->header('Content-Range', "bytes $offset-$offset_end/$file_size");
        }
        $handle = \fopen($file, 'r');
        if ($offset !== 0) {
            \fseek($handle, $offset);
        }
        $size = 1024 * 1024;
        $buffer = fread($handle, $size);
        Gateway::sendToCurrentClient((string) $response . $buffer);
        while (!feof($handle)) {
            $buffer = fread($handle, $size);
            Gateway::sendToCurrentClient($buffer);
        }
        fclose($handle);
    }

    /**
     * 添加处理器
     * @param Annotation $parse
     */
    protected function addCall(AnnotationHandle $parse) {
        static $set = false;
        if ($set) {
            return;
        }
        $set = true;
        $parse->addCall(function (array $params)use ($parse) {
            $connection = $this->getTcpConnection();
            $request = Http::decode($params[0], $connection);
            try {
                // 静态文件处理
                $result = $this->requestStatic($request);
                if (is_null($result)) {
                    $result = $parse->callIndex('http-router', $request->method() . $request->path(), $request);
                    if (is_null($result)) {
                        $result = $parse->callIndex('http-router', $request->path(), $request);
                        if (is_null($result)) {
                            $result = $parse->callIndex('bind-call', 'http', $request);
                        }
                    }
                }
            } catch (BusinessException $err) {
                $result = $parse->callIndex('bind-call', 'http', $request);
            } catch (\Exception $err) {
                $result = $parse->callIndex('bind-call', 'http', $request);
                BusinessWorker::log('[ERROR] ' . $err->getMessage() . PHP_EOL . $err->getTraceAsString());
            }
            if (is_null($result)) {
                return;
            }
            if (!$result instanceof Response) {
                $result = new Response(200, [], $result);
            } elseif (isset($result->file)) {
                $file = $result->file['file'];
                if (file_exists($file) && is_readable($file)) {
                    // 发送文件
                    $this->sendFile($result);
                    return;
                }
                $result = new Response(403, null, '403 Forbidden');
            }
            return Http::encode($result, $connection);
        });
    }

    /**
     * 注解处理数据生成
     * @param array $params
     * @param array $input
     * @return array
     */
    public function make(array $params, array $input): array {
        $indexs = [];
        foreach ($params as $param) {
            $indexs[$param['path']] = $param['path'];
        }
        $this->addCall($input['parse']);
        return [
            'http' => $indexs,
        ];
    }

}
