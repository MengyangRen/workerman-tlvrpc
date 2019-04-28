<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 * 异步调用
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
	$req1 = $rpcClient->AsyncUserService->getInfoByUid(1000);
	$req2 = $rpcClient->AsyncUserService->hello(2000);
	$req3 = $rpcClient->AsyncExampleService->hello(1000);
	//这里是其它的业务代码
	var_dump($req1->result,$req2->result,$req3->result);
} catch (\Exception $e) {
	printf("异常信息 %s \r\n",$e->getMessage());
}


