<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  客户端加载文件
 * 
 * @package			tlvRpc
 * @subpackage		server
 * @author			v.r
 * 
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../libs/Logger.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../libs/Std.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../libs/RpcUriManage.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../libs/Weight.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../libs/ShmManage.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../protocols/Tlv.php';
require_once dirname(__FILE__) . '/RpcClient.php';
require_once dirname(__FILE__) . '/RpcConfig.php';