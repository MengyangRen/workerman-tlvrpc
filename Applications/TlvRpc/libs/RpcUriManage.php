<?php
namespace Libs;


/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  服务地址管理
 *  
 * @author  v.r
 * @package PhpRpc.Core.RpcUriManage
 *
 *  需支持（sysshm）
 * 
 */

class RpcUriManage {
	public $maps = array();
	const  BAD_ADDR_KEY = 0x90905743;
	public $service;
	public $conf;
	public $uris;

	public function __construct($conf = null,$service = null){
		if (empty($conf) || empty($service))
			return false;
		$this->conf = $conf;
		$this->service = $service;
	}

	public function laodUri() {
		$size = count($this->uris);
		if ($size == 1) 
			return $this->uris[0];
        //$this->uris = 过滤掉故障地址->查询共享内存进行处理
        //获取地址权重
        return  (new Weight($this->uris))
         	->formatWeightArr()
         	->getWeightSum()
         	->getWeightInterval()
         	->compute();
	}
	public function doUriToMap() {
	    if (strpos($this->conf['uri'],','))
	       $this->uris = explode(',',$this->conf['uri']);
	    else
	       $this->uris = (array) $this->conf['uri'];
	    return $this;
	}

	public function addUri($rpcUri) {
		return true;
	}

	//恢复一个地址
	public function recoverUri() {
		return true;
	}
	
	//检查地址是否恢复
	public function checkUri() {
		return true;
	}
}
