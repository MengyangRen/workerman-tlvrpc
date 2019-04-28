<?php
namespace Clients;

use Libs\Std;
use Libs\RpcUriManage;
use Libs\Weight;
use Libs\ShmManage;
use Clients\Transport;
use Clients\AsyncRequestWrapper;
use Protocols\Tlv;
use Protocols\JsonRequest;
use Protocols\JsonResponse;


/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  phprpc 客户端
 *  
 * @author  v.r
 * @package PhpRpc.clicent.RpcClient
 *
 *
 * @explain
 * 
 * 1.客户端负载均衡
 * 2.故障节点自动剔除及健康嗅探
 * 3.支持权重设置
 * 4.支持异步调用
 * 5.支持日志追踪
 * 
 * @usage
 *  1, 复制或软链接 RpcClient目录到具体的项目目录中
 *  2, 添加 RpcServer 相关配置, 参考: examples/config/debug.php
 *  3. 客户端环境支持 php7+ , event,shmsys,socket,等扩展支持
 *   
 *
 * Tlv传输协议客户端实现
 * 1. 可以在各种语言中简单的实现 
 * 2. 并且解析性可以做到与二进制协议接近。
 * 
 * 协议结构 {
 *    Tag(命令)
 *    len (长度)
 *    body
 * }
 * 
 * 网络流
 * 03e80000010b{"method":["ExampleService","hello"],"params":[1000],"version":null,"user":"test","password":"7e04d8576a59833a1dd9e04ad7a27c23","timestamp":1556129969.776097,"secret":"{1BA09530-F9E6-478D-9965-7EB31A59537E}","signature":"b171134924f61ae98a5d968e94d3106a","traceId":1}
 * 
 * 
 * @example
 *   RpcClient::config(new RpcConfig);
 *   rpcClient = new RpcClient;
 *   $re1 = $rpcClient->UserService->getInfoByUid(1000);
 *
 * 
 */


class RpcClient 
{
    
    //连接读写对象
    protected $transport;
    //rpc服务当前可用地址
    protected $rpcUri;
    //当前服务名
    public $service;
    //当前服务配置
    protected $config;
    
    //是否为异步标志 
    public    $asyncSend = false;
    //全局配置
    protected static $globConfig;
    //同步实例
    protected static $instances;
    //异步实例
    public static $asyncInstances;


    /**
     *  phprpc客户端初始化
     * 
     * @param obj $globConfig
     *   RPC客户端的服务配置
     * 
     * @param sting $service
     *   访问一个RPC服务的服务名
     * 
     * @return void
     * @explain
     * 
     *  1.加载当前服务配置
     *  2.加载rpc地址
     *  
     */
    public function __construct($globConfig = null, $service = null) {
        if (empty($globConfig) || empty($service))  
            return false;

        if (!strpos($service,'Service')) return false;

        $attr = self::getServiceConfAttr($service);
        if (!property_exists($globConfig,$attr))
             throw new \Exception("[error] ".$service."Service exception you requested, 
                ot in service configuration entry");
       
        $this->service = $service;
        $this->config = $globConfig->$attr;
        $this->laodRpcUri()->loadClient(); 
    }

    /**
     *  phprpc客户端初属性重载
     * 
     * @param sting $property
     *   访问一个RPC服务的同步或异步服务名
     * 
     * @return void
     * @explain
     * 
     *  1.同步请求
     *          设置过：则返回当前实例并重载加载rpc客户端,
     *          未设置：重新实例化
     *          
     *  2.异步请求
     *         设置过：则返回当前异步实例,
     *         未设置：重新实例化
     *  
     */
    public function __get($property) {
        
        if (!strpos($property,'Service')) 
            return false;

        $key      = self::makeServiceObjectKey($property);
        $service  = self::getServiceForProperty($property);
        
        //异步请求
        if (self::isAsyncRequest($property)) 
            return self::getAsyncInstance($key,$service);    

        //同步请求
        if (!self::isAsyncRequest($property)) 
            return self::getSyncInstance($key,$service);
    }


    /**
     *  phprpc客户端方法重载
     * 
     * @param sting $method
     *   访问一个RPC服务的方法名
     * 
     * @param array $arg
     *   访问一个RPC服务的方法所需要的参数
     *   
     * @return void
     * @explain
     * 
     *  
     * 解决异步服务异常情况
     * 1. 不同方法组合调用异常
     * 2. 相同方法不同参数调用异常
     * 3. 相同方法相同参数调用异常
     * 
     */
    public function __call($method, $args) {
        try {

            $_jsonData = array(
                Tlv::TAG_RPC_CLIENT_SEND,
                self::makeJsonRequestData($this->service,$method,$args)
            );
            //异步
            if ($this->asyncSend) {
                $key = $this->service.$method.serialize($args);
                $key = self::makeServiceObjectKey($key);
                $asyncObj = self::getAsyncInstance($key,$this->service); 
                $asyncObj->transport->send($_jsonData);
                return new AsyncRequestWrapper($asyncObj->transport,$this->service,$method,$args);
            }

            //同步
            $this->transport->send($_jsonData);
            $result = $this->transport->recv();
            Std::debug(
                sprintf("RpcClient同步调用: %s->%s(%s) [耗时](%.3fs) \r\n[结果] %s", 
                $this->service,$method,
                join(',',$args),
                $this->transport->et(),
                var_export($result,true)),
               __CLass__
            );
            return $result;
        } catch (\Exception $e) {
            throw new \Exception('RPC Exception: '.$this->service.'::'.$method. ' '.$e->getMessage());
        }
    }
   
    /**
     *  获取一个同步实例
     * 
     * @param sting $service
     *   访问一个RPC服务的同步或异步服务名
     *   
     * @param array $key
     *   实例KEY
     *   
     * @return 实例对象
     *
     */
    public static function getSyncInstance($key,$service) {
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self(self::$globConfig,$service);
            return self::$instances[$key];
        }
        return  self::$instances[$key]->reloadClient();
    }
   
    /**
     *  获取一个异步实例
     * 
     * @param sting $service
     *   访问一个RPC服务的同步或异步服务名
     *   
     * @param array $key
     *   实例KEY
     *   
     * @return 实例对象
     *
     */
    public static function getAsyncInstance($key,$service) {
        if (!isset(self::$asyncInstances[$key])) {
            self::$asyncInstances[$key] = new self(self::$globConfig,$service);
            self::$asyncInstances[$key]->asyncSend = true;
            return self::$asyncInstances[$key];
        }
        return  self::$asyncInstances[$key];
    }

   
    /**
     *  生成一个json请求数据集
     * 
     * @param sting $service
     *   访问一个RPC服务的同步或异步服务名
     *   
     * @param array $method
     *   访问一个RPC服务的方法名
     * 
     * @param array $arg
     *   访问一个RPC服务的方法所需要的参数
     *
     * @param int $traceId
     *   上下文联系，主要支持日志追踪
     * 
     * @param array $config
     *   密钥等一系列配置
     *    
     * 
     * @return json对象
     *
     *  
     */
    public static function makeJsonRequestData ($service,$method,$args,$traceId = null,$config = null) {
        $request = new JsonRequest(
            !empty($service) && is_string($method) ? array($service, $method) : $method,
            $args, 
            empty($traceId) ? JsonRequest::NEXTID : $traceId
        );
        return $request->encrypt($request->__toJson(),$request->secret)->__toJson();
    }



    public    function reloadClient() {   $this->laodRpcUri()->loadClient(); return $this;}
    protected function laodRpcUri()   {   $this->rpcUri = (new RpcUriManage($this->config, $this->service))->doUriToMap()->laodUri(); return $this; }
    protected function loadClient()   {   $this->transport = new Transport; $this->transport->open($this->rpcUri);}

    public    static function config($config) {if (!empty($config)) self::$globConfig = $config; }
    protected static function getServiceConfAttr($service) {     return trim(str_replace('Service','', $service));}
    protected static function getServiceForProperty($property) { return self::isAsyncRequest($property)? trim(str_replace('Async','', $property)) : $property; }
    protected static function isAsyncRequest($property) {   return false !== strpos($property,'Async') ? true : false; }
    protected static function makeServiceObjectKey($d) { return md5($d);}
   
}

/**
 * Class AsyncRequestWrapper 异步请求重新封装
 * @package PhpRpc.clicent.RpcClient
 */

class AsyncRequestWrapper{
    protected $transport;
    protected $service;
    protected $method;
    protected $args;

    public function __construct($transport, $service, $method,$args){
        $this->transport = $transport;
        $this->service = $service;
        $this->method = $method;
        $this->args = $args;
    }
    /**
     * 异步请求,通过result属性获取服务端返回结果。
     * @param $name
     * @return array
     * @throws Exception
     */
    public function __get($name){
        if($name == 'result'){
            try {
                $result = $this->transport->recv();
                Std::debug(
                    sprintf("RpcClient异步调用: %s->%s(%s) [耗时](%.3fs) \r\n[结果] %s", 
                    $this->service,$this->method,
                    join(',',$this->args),
                    $this->transport->et(),
                    var_export($result,true)),
                    __CLass__
                );
                return $result;
            } catch(\Exception $ex){
                throw new Exception('RPC Exception: '.$this->service.'::'.$this->method.' '.$ex->getMessage());
            }
        }
    }
}

/**
 * Class Transport 客户端连接通道封装
 * @package PhpRpc.clicent.RpcClient
 */
class Transport {
    public $rpcUri;
    public $conn;
    public $cto = 4;
    public $ets;
    const  CONN_TRY_TIMEOUT = 1;

    /**
     *  打开一个rpc远程服务
     * 
     * @param sting $rpcUri
     *   访问一个RPC服务器的tcp地址
     *
     * 
     * @return 资源
     *
     * 1.远程服务异常重试机制
     * 2.异常服务地址丢弃
     * 
     * 
     */
    public function open($rpcUri) {
        $this->ets = microtime(true);
        $this->rpcUri = $rpcUri;
        $conn = stream_socket_client($this->rpcUri, $errno, $errstr);
        // try one
        if (!$conn && $errno == 110) {
            $timeout = (int)(self::$cto - static::CONN_TRY_TIMEOUT);
            if ($timeout > 0) {
                $conn = stream_socket_client($this->rpcUri, 
                    $errno, 
                    $errstr, 
                    $timeout
                );
            }
        }
        // uri error
        if (!$conn) {
           (new RpcUriManage)->addUri($rpcUri);
           throw new \Exception(sprintf('RpcClient: %s, %s (%.3fs)', 
            $this->rpcUri, $errstr, $this->et()));
        }

        stream_set_blocking($conn,true);
        //stream_set_timeout($conn,1);
        stream_set_timeout($conn,0,100);
        $this->conn = $conn;
        return $conn;
    }

    /**
     *  向rpc远程服务发送数据
     * 
     * @param sting $data
     *   一个json请求数据
     *   
     * @return void
     *
     * 1.json文本数据会被转成 TLv协议
     * 2.默认读写超时为 5s
     * 3.读取超时会有异常触发
     *    
     */
    public function send($data) {
        $buf = TLV::encode($data)."\n";
        while (strlen($buf) > 0) {
            $got = fwrite($this->conn, $buf);
            if ($got === 0 || $got === FALSE) {
                $md = stream_get_meta_data($this->conn);
                if ($md['timed_out']) {
                    throw new \Exception('Transport: timed out writing '.strlen($buf));
                } else {
                    throw new \Exception('Transport: Could not write buf');
                }
            }
            $buf = substr($buf, $got);
        }
    } 

    /**
     *  接受rpc远程服务数据
     * 
     * @param void
     * @return json数据
     * 
     * 1.fgets 注意读取行数据，数据量大时请自行修改
     * 2.远程服务Tlv协议转义成json对象数据 
     *   
     */
    public function recv() {
        if (empty($buff = fgets($this->conn))) 
            throw new \Exception("recv data is empty");
        $this->close();
        $response = Tlv::decode($buff);
        if ($response->error) {
            throw new \Exception($response->error);
        }
        return $response->result;
    }

    /**
     *  关闭当前连接
     * 
     * @param   void
     * @return  void
     */
    public function close() {
        fclose($this->conn);
        $this->conn = null;
    }

    public function et() { return microtime(true) - $this->ets; }
}


