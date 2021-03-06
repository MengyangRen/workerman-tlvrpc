<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  服务端启动文件
 * 
 * @package         tlvRpc
 * @subpackage      server
 * @author          v.r
 * 
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'libs/Logger.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'libs/Std.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'libs/RpcWorker.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'protocols/Tlv.php';

use Workerman\Worker;
use Libs\RpcWorker;
use Protocols\Tlv;
use Protocols\JsonResponse;


if (!defined("SERVICE_BOOTSTRAP"))
    define('SERVICE_BOOTSTRAP', dirname(__FILE__).'/src/init.php');

$worker = new Worker('Tlv://0.0.0.0:2015');
$worker->count = 3;
$worker->name = 'TlvRpc';
$rpcWorker = new RpcWorker;
$rpcWorker->load()->addServices();
$worker->onMessage = function($connection, $request) use($rpcWorker)
{
    $service = $request->method[0];
    $method = $request->method[1];
    //认证业务
    try {
        return $connection->send(array(
            TLV::TAG_RPC_SERVER_SEND,
            $rpcWorker->process($request)->__toJson()
        ));
     } catch (\Exception $e) {
        return $connection->send(array(
            TLV::TAG_RPC_SERVER_SEND,
            (new JsonResponse(null,array($service,$method),$e->getMessage(), $request->traceId))->__toJson()
        ));
     }
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
