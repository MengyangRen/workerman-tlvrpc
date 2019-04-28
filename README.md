# workerman-tlvrpc

### TLV协议简介
 即Tag（Type）-Length-Value，是一中简单实用的数据传输方案。在TLV的定义中，可以知道它包括三个域，分别为：标签域（Tag），长度域（Length），内容域（Value）。这里的长度域的值实际上就是内容域的长度，其实就是一个简单的自定义通信协议，将对应的数据进行编码、组织

 相对于二进制协议，TLV文本协议，也容易被实现，并且解析性可以做到与二进制协议接近

### 结构:
    {
       Tag (命令/标签域)->2*2
       Len (长度域)  -> 4*2 
       Body (内容域)
    }

### 例子:
     03e8
     0000010b
     {"method":["ExampleService","hello"],"params":[1000],"version":null,"user:test,traceId":1.....}


### 环境需求
* \>= PHP 7
* event extensions for PHP  
* sysvshm extensions for PHP  
### TLV客户端支持
 * 1.支持同步调用
 * 2.支持异步调用
 * 3.支持混合调用
 * 4.支持负载均衡
 * 5.支持权重设置
 * 6.故障节点自动剔除及健康嗅探 （event.so）支持
 * 7.日志追踪
 
### 组件说明
 * 1.提供互斥锁组件
 * 2.共享内存管理组件
 * 3.日志组件
 * 4.标准输出组件
 * 5.地址管理组件
 * 6.权重算法组件
 * 7.自动检查故障节点异常，恢复等组件
 

### 同步调用
```PHP
    try {
        
        Clients\RpcClient::config(new Clients\RpcConfig);
        $rpcClient = new Clients\RpcClient;
        $req1 = $rpcClient->AsyncUserService->getInfoByUid(1000);
        $req2 = $rpcClient->AsyncUserService->hello(2000);
        $req3 = $rpcClient->AsyncExampleService->hello(1000);
        //这里是其它的业务代码
        var_dump($req1->result,$req2->result,$req3->result);
    } catch (\Exception $e) {
        printf("异常信息 %s \r\n",$e->getMessage());
    }

```
![demo1](https://github.com/MengyangRen/workerman-tlvrpc/blob/master/img/syncCall.png)
### 异步调用
```PHP

    try {

        Clients\RpcClient::config(new Clients\RpcConfig);
        $rpcClient = new Clients\RpcClient;
        $re1 = $rpcClient->UserService->getInfoByUid(1000);
        $re2 = $rpcClient->ExampleService->hello(1000);
        $re3 = $rpcClient->ExampleService->hello2(1000);
        
        var_dump($re1);
        var_dump($re2);
        var_dump($re3);
    } catch (\Exception $e) {
        printf("异常信息 %s \r\n",$e->getMessage());
    }
```
![demo2](https://github.com/MengyangRen/workerman-tlvrpc/blob/master/img/asyncCall.png)
### 混合调用
```PHP

    try {

        Clients\RpcClient::config(new Clients\RpcConfig);
        $rpcClient = new Clients\RpcClient;
        $re1 = $rpcClient->UserService->getInfoByUid(1000);
        $re2 = $rpcClient->ExampleService->hello(1000);
        $re3 = $rpcClient->ExampleService->hello2(1000);
        
        
        $req1 = $rpcClient->AsyncUserService->getInfoByUid(1000);
        $req2 = $rpcClient->AsyncUserService->hello(2000);
        $req3 = $rpcClient->AsyncExampleService->hello(1000);

        print "同步调用块:".PHP_EOL;
        var_dump($re1);
        var_dump($re2);
        var_dump($re3);

        //这里是其它的业务代码
        print "异步调用块:".PHP_EOL;
        var_dump($req1->result,$req2->result,$req3->result);


    } catch (\Exception $e) {
        printf("异常信息 %s \r\n",$e->getMessage());
    }

```
![demo3](https://github.com/MengyangRen/workerman-tlvrpc/blob/master/img/minCall.png)

### 负载均衡与权重设置

```PHP
    try {
        

        //设置权重
        $RpcConfig = new Clients\RpcConfig;
        $RpcConfig->User['uri']   = "tcp://127.0.0.1:2015:20,tcp://127.0.0.1:2015:40,tcp://127.0.0.1:2015:60";
        $RpcConfig->Example['uri'] = "tcp://127.0.0.1:2015:20,tcp://127.0.0.1:2015:40,tcp://127.0.0.1:2015:60";
        
        Clients\RpcClient::config($RpcConfig);
        $rpcClient = new Clients\RpcClient;
        $re2 = $rpcClient->ExampleService->hello(1000);
        $re1 = $rpcClient->UserService->hello(2000);

        var_dump($re1);
        var_dump($re2);

    } catch (\Exception $e) {
        printf("异常信息 %s \r\n",$e->getMessage());
    }
```
![demo4](https://github.com/MengyangRen/workerman-tlvrpc/blob/master/img/loadlevel.png)
...