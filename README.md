# workerman-tlvrpc

## 协议说明
##结构:

>  协议结构 {
     Tag (命令)
     Len (长度)
     Body (内容)
}
 
##例子:

     03e8
     0000010b
     {"method":["ExampleService","hello"],"params":[1000],"version":null,"user":"test","password":"7e04d8576a59833a1dd9e04ad7a27c23","timestamp":1556129969.776097,"secret":"xx","signature":"xx","traceId":1}


## 环境需求
* \>= PHP 7
* A POSIX compatible operating system (Linux, OSX, BSD)  
* evnent extensions for PHP  
* sysvshm extensions for PHP  

## tlvRPC客户端支持
 * 1.支持同步调用
 * 2.支持异步调用
 * 3.支持混合调用
 * 4.支持负载均衡
 * 5.支持权重设置
 * 6.故障节点自动剔除及健康嗅探 （event.so）支持
 * 7.日志追踪


## 同步调用
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
## 异步调用
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
## 混合调用
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

## 负载均衡与权重设置

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