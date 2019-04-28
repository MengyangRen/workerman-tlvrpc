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
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'clients/StatisticClient.php';

use Workerman\Worker;
use Libs\RpcWorker;
use Protocols\Tlv;
use Protocols\JsonResponse;

define('SERVICE_BOOTSTRAP', dirname(__FILE__).'/src/init.php');
define('STATISTIC_ADDRESS','udp://127.0.0.1:55656');

$worker = new Worker('Tlv://0.0.0.0:2015');
$worker->count = 3;
$worker->name = 'TlvRpc';
$rpcWorker = new RpcWorker;
$rpcWorker->load()->addServices();
$worker->onMessage = function($connection, $request) use($rpcWorker)
{

    $service = $request->method[0];
    $method = $request->method[1];
    StatisticClient::tick($service, $method);
    //认证业务
    try {

       StatisticClient::report($service, $method, 1, 0, '',STATISTIC_ADDRESS);
        $ret = array(
            TLV::TAG_RPC_SERVER_SEND,
            $rpcWorker->process($request)->__toJson()
        );
        return $connection->send($ret);
     } catch (\Exception $e) {
        StatisticClient::report($service,0, $success, $e->getCode(), $e,STATISTIC_ADDRESS);
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
