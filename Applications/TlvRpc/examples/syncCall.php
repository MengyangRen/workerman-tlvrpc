<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 * 同步调用
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
