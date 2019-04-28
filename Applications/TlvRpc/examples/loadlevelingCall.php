<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 * 基于权重负载均衡
 * 
 * @author  v.r
 * @package PhpRpc.examples
 * 
 */

define ('DEV', 1);
define('ENV', DEV);

date_default_timezone_set('Asia/Shanghai');
require_once dirname(__FILE__) .DIRECTORY_SEPARATOR.'../clients/Client.php';

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

