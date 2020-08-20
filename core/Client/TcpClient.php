<?php

namespace Core\Client;

use Core\Conf;

/**
 * TCP客户端
 */
class TcpClient
{
    const OK = 200;

    const DECODE_PHP = 1;   //使用PHP的serialize打包
    const DECODE_JSON = 2;   //使用json_encode打包
    const DECODE_MSGPACK = 3;   //使用msgpack打包
    const DECODE_SWOOLE = 4;   //使用swoole_serialize打包
    const DECODE_GZIP = 128; //启用GZIP压缩

    /**
     * 配置信息
     */
    protected $config = array();

    /**
     * 环境变量
     */
    protected $env = array();

    /**
     * 连接到服务器
     */
    protected $connections = array();

    /**
     * 超时时间
     */
    protected $timeout = 10;

    /**
     * 启用长连接
     */
    protected $keepConnection = false;

    /**
     * 使用协程客户端
     */
    protected $coroutineClient = true;

    /**
     * 是否存在swoole扩展
     */
    protected $haveSwoole = false;

    /**
     * 单例，非阻塞模式下请勿使用
     */
    protected static $_instances;

    /**
     * 是否压缩消息体
     */
    protected $encode_gzip = false;

    /**
     * 格式化类型
     */
    protected $encode_type = self::DECODE_JSON;

    /**
     * 应用
     */
    private $app;

    /**
     * 控制器
     */
    private $controller;

    /**
     * 服务方法名
     */
    private $action;

    /**
     * 参数
     */
    private $args = [];

    function __construct(array $config = array())
    {
        if (empty($config)) {
            $config = Conf::get('appTcp');
        }
        $this->config = $config;
        $this->haveSwoole = extension_loaded('swoole');
    }

    /**
     * 设置使用长连接
     * @param bool $keepConnection
     */
    public function setKeepConnection($keepConnection)
    {
        $this->keepConnection = $keepConnection;
        return $this;
    }

    /**
     * 设置使用协程客户端
     * @param $coroutineClient
     */
    public function setCoroutineClient($coroutineClient)
    {
        $this->coroutineClient = $coroutineClient;
        return $this;
    }

    /**
     * 设置编码类型
     * @param $type
     * @param $gzip
     * @throws \Exception
     */
    public function setEncodeType($type, $gzip)
    {
        if ($type === self::DECODE_SWOOLE and (substr(PHP_VERSION, 0, 1) != '7')) {
            throw new \Exception("swoole_serialize only use in phpng");
        } else {
            $this->encode_type = $type;
        }
        if ($gzip) {
            $this->encode_gzip = true;
        }
        return $this;
    }

    /**
     * 获取服务实例，非阻塞模式请勿使用
     * @param array $config
     * @return mixed|static
     * @throws \Exception
     */
    static function getInstance(array $config = array())
    {
        if (empty(self::$_instances)) {
            $object = new self($config);
        } else {
            $object = self::$_instances;
        }
        return $object;
    }

    /**
     * 生成请求串号
     * @return int
     */
    static function getRequestId()
    {
        $us = strstr(microtime(), ' ', true);
        return intval(strval($us * 1000 * 1000) . rand(100, 999));
    }

    /**
     * 关闭连接
     * @param $host
     * @param $port
     * @return bool
     */
    protected function closeConnection($host, $port)
    {
        $conn_key = $host . ':' . $port;
        if (!isset($this->connections[$conn_key])) {
            return false;
        }
        $socket = $this->connections[$conn_key];
        $socket->close(true);
        unset($this->connections[$conn_key]);
        return true;
    }

    /**
     * 获取连接对象
     * @param $host
     * @param $port
     * @return bool|Stream|TCP|mixed|\swoole_client
     */
    protected function getConnection($host, $port)
    {
        $ret = false;
        $conn_key = $host . ':' . $port;
        if (isset($this->connections[$conn_key])) {
            return $this->connections[$conn_key];
        }
        //基于Swoole扩展
        if ($this->haveSwoole) {
            if ($this->coroutineClient) {
                $socket = new \Co\client(SWOOLE_SOCK_TCP);
            } elseif ($this->keepConnection) {
                $socket = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
            } else {
                $socket = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
            }
            $socket->set(array(
                'package_eof' => "\r\n",
            ));

            /**
             * 尝试重连一次
             */
            for ($i = 0; $i < 2; $i++) {
                $ret = $socket->connect($host, $port, $this->timeout);
                if ($ret === false and ($socket->errCode == 114 or $socket->errCode == 115)) {
                    //强制关闭，重连
                    $socket->close(true);
                    continue;
                } else {
                    break;
                }
            }
        }

        if ($ret) {
            $this->connections[$conn_key] = $socket;
            return $socket;
        } else {
            return false;
        }
    }

    /**
     * 连接到服务器
     * @param TcpResponse $retObj
     * @return bool
     * @throws \Exception
     */
    protected function connectToServer($retObj)
    {
        $config = $this->getConfig();
        $socket = $this->getConnection($config['host'], $config['port']);
        //连接失败，服务器节点不可用
        if ($socket !== false) {
            $retObj->socket = $socket;
            $retObj->server_host = $config['host'];
            $retObj->server_port = $config['port'];
            return true;
        }
        return false;
    }

    /**
     * 获取环境变量
     * @return array
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * 设置环境变量
     * @param array $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * 设置一项环境变量
     * @param $k
     * @param $v
     */
    public function putEnv($k, $v)
    {
        $this->env[$k] = $v;
    }

    /**
     * 设置超时时间，包括连接超时和接收超时
     * @param $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 设置调用服务的应用
     *
     * @param string $app
     * @return self
     */
    public function setApp($app = null)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * 设置调用服务的控制器
     *
     * @param string $controller
     * @return self
     */
    public function setController($controller = null)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * 设置调用服务的方法
     *
     * @param string $action
     * @return self
     */
    public function setAction($action = null)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置调用服务的参数
     *
     * @param array $args
     * @return self
     */
    public function args(array $args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * 设置接口请求方式
     * @param string $method
     * @return self
     */
    public function setMethod($method = null)
    {
        return $this;
    }

    /**
     * TCP调用
     */
    public function run()
    {
        $retObj = new TcpResponse();
        $baseArgs = [
            'app' => $this->app,
            'controller' => $this->controller,
            'action' => $this->action,
            'time' => time(),
        ];

        $send = array_merge($baseArgs, $this->args);
        if (count($this->env) > 0) {
            //调用端环境变量
            $send['env'] = $this->env;
        }
        $this->request($send, $retObj);

        return ['code' => $retObj->code, 'message' => $retObj->msg, 'result' => $retObj->data];
    }

    /**
     * 发送请求
     * @param $send
     * @param TcpResponse $retObj
     * @return bool
     */
    protected function request($send, $retObj)
    {
        $retObj->send = $send;

        connect_to_server:
        if ($this->connectToServer($retObj) === false) {
            $retObj->code = TcpResponse::ERR_CONNECT;
            return false;
        }

        //请求串号
        $retObj->requestId = self::getRequestId();

        //打包格式
        $encodeType = $this->encode_type;
        if ($this->encode_gzip) {
            $encodeType |= self::DECODE_GZIP;
        }

        //发送失败了
        if ($retObj->socket->send(self::encode($retObj->send, $encodeType, $retObj->requestId)) === false) {
            $this->closeConnection($retObj->server_host, $retObj->server_port);
            //连接被重置了，重现连接到服务器
            if ($this->haveSwoole and $retObj->socket->errCode == 104) {
                goto connect_to_server;
            }
            $retObj->code = TcpResponse::ERR_SEND;
            unset($retObj->socket);
            return false;
        }

        //接收响应
        $this->wait($retObj);
        return true;
    }

    /**
     * 接收响应
     * @param $retObj
     * @return bool
     */
    public function wait($retObj)
    {
        $st = microtime(true);
        while (1) {
            //发生超时
            if (round(microtime(true) - $st) >= $this->timeout) {
                $retObj->code = ($retObj->socket->isConnected()) ? TcpResponse::ERR_TIMEOUT : TcpResponse::ERR_CONNECT;
                $this->closeConnection($retObj->server_host, $retObj->server_port);
                return false;
            }

            $data = $retObj->socket->recv();
            //socket被关闭了
            if ($data === "") {
                $retObj->code = TcpResponse::ERR_CLOSED;
                $this->closeConnection($retObj->server_host, $retObj->server_port);
                return false;
            } elseif ($data === false) {
                continue;
            }

            $data = self::decode($data, $this->encode_type);
            //错误的请求串号
            if (!isset($data['env']['requestId']) || $data['env']['requestId'] != $retObj->requestId) {
                continue;
            }

            //成功处理
            $this->finish($data, $retObj);
            return true;
        }
    }

    /**
     * 完成请求
     * @param $retData
     * @param $retObj TcpResponse
     */
    protected function finish($retData, $retObj)
    {
        //解包失败了
        if ($retData === false) {
            $retObj->code = TcpResponse::ERR_UNPACK;
            $retObj->msg = 'unpack fail';
        } //调用成功
        elseif ($retData['code'] === self::OK) {
            $retObj->code = self::OK;
            $retObj->data = $retData['result'];
        } //服务器返回失败
        else {
            $retObj->code = $retData['code'];
            $retObj->data = null;
            $retObj->msg = $retData['message'];
        }

        if (!$this->keepConnection) {
            $this->closeConnection($retObj->server_host, $retObj->server_port);
        }
    }

    /**
     * 关闭所有连接
     */
    public function close()
    {
        foreach ($this->connections as $key => $socket) {
            /**
             * @var $socket \swoole_client
             */
            $socket->close(true);
            unset($this->connections[$key]);
        }
    }

    /**
     * 打包数据
     * @param $data
     * @param $type
     * @param $serid
     * @return string
     */
    static function encode($data, $type = self::DECODE_JSON, $serid = 0)
    {
        //调用端环境变量
        if (isset($data['env'])) {
            $data['env']['requestId'] = $serid;
        } else {
            $data['env'] = ['requestId' => $serid];
        }

        //启用压缩
        if ($type & self::DECODE_GZIP) {
            $_type = $type & ~self::DECODE_GZIP;
            $gzip_compress = true;
        } else {
            $gzip_compress = false;
            $_type = $type;
        }
        switch ($_type) {
            case self::DECODE_JSON:
                $body = json_encode(self::handleGarbledString($data));
                break;
            case self::DECODE_SWOOLE:
                $body = \swoole_serialize::pack($data);
                break;
            case self::DECODE_PHP:
            default:
                $body = serialize($data);
                break;
        }
        if ($gzip_compress) {
            $body = gzencode($body);
        }
        return $body . "\r\n";
    }

    /**
     * 解包数据
     * @param string $data
     * @param int $unseralize_type
     * @return string
     */
    static function decode($data, $unseralize_type = self::DECODE_JSON)
    {
        if ($unseralize_type & self::DECODE_GZIP) {
            $unseralize_type &= ~self::DECODE_GZIP;
            $data = gzdecode($data);
        }
        switch ($unseralize_type) {
            case self::DECODE_JSON:
                return json_decode($data, true);
            case self::DECODE_SWOOLE:
                return \swoole_serialize::unpack($data);
            case self::DECODE_PHP;
            default:
                return unserialize($data);
        }
    }

    public function getConfig()
    {
        $config = $this->config[strtolower($this->app)];

        if (empty($config) || !isset($config['host']) || !isset($config['port'])) {
            throw new \Exception('Service configuration error');
        }

        return $config;
    }

    /**
     * 调用完成关闭连接
     */
    public function __destruct()
    {
        if ($this->connections) {
            foreach ($this->connections as $socket) {
                $socket->close(true);
            }
            unset($this->connections);
        }
    }

    /**
     * 处理乱码，使得在进行json_encode的时候进行编码的不是乱码，防止json_encode失败
     * @param null $var
     * @return array|null
     */
    public static function handleGarbledString($var = null)
    {

        if (empty($var) || !is_array($var)) {
            return $var;
        }

        foreach ($var as $k => $v) {
            //键名$k也有乱码
            if (is_string($k)) {
                if (!mb_check_encoding($k, 'UTF-8')) {
                    unset($var[$k]);//去掉乱码的键值对
                    $k = self::filterGibberishCode($k);
                }
            }
            //值处理
            if (is_string($v)) {
                if (!mb_check_encoding($v, "UTF-8")) {
                    $v = self::filterGibberishCode($v);
                }
            } elseif (is_array($v)) {
                $v = self::handleGarbledString($v);
            }
            $var[$k] = $v;
        }

        return $var;
    }

    /**
     * @desc 过滤无效乱码字符
     */
    public static function filterGibberishCode($source = null)
    {
        if (!$source) {
            return $source;
        }

        if (!is_string($source)) {
            $source = (string)$source;
        }

        $leng = mb_strlen($source, 'UTF-8');
        $sourceArray = array();
        for ($i = 0; $i < $leng; $i++) {
            $sourceArray[] = mb_substr($source, $i, 1, "UTF-8");
        }

        $res = array();
        foreach ($sourceArray as $key => $string) {
            $isUtf = mb_check_encoding($string, "UTF-8");
            if ($isUtf) {
                $res[] = $string;
            } else {
                //避免多个空格
                $rLength = count($res);
                $rKey = ($rLength - 1) > 0 ? ($rLength - 1) : 0;
                if ($res[$rKey] != ' ') {
                    $res[] = ' ';
                }
            }
        }

        $source = implode('', $res);
        $source = trim($source, ' ');

        return $source;
    }
}