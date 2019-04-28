<?php
namespace Clients;

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  phprpc配置项
 *  
 * @author  v.r
 * @package PhpRpc.client.RpcConfig
 *
 * 
 */
class RpcConfig {
    //日记记录
    public $secretKey = '777af463a39f077a0340a189e9c1ec28';
    public $recvTimeOut = 5;
    public $connTimeOut = 5;
    public $rtbin ='./bin/rpc-timer'; 

    //提供服务列表
    public $User = array(
        'uri' => 'tcp://127.0.0.1:2015',
        'user' => 'Test',
        'secret'=> '{9BS09530-F9E6-476D-7777-7EB31A59537E}',
    );

    public $Example = array(
        'uri' => 'tcp://127.0.0.1:2015',
        'user' => 'Test',
        'secret'=> '{9BS09530-F9E6-476D-7777-7EB31A59537E}',
    );
}
