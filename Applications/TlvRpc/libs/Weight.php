<?php
namespace Libs;

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  权重轮询调度算法
 * @author  v.r
 * @package PhpRpc.Core.Weight
 * 
 */

class Weight 
{
	public $map;
	public $len;
	public $ws; //总权重
	public $flag = true;

	//初始化
	public function __construct(array $map) {
			$this->map = $map;
			$this->len = count($map); 
	}
	
	//处理非标准格式的权重集
	//array(
	// tcp://127.0.0.1:4567:10
	// tcp://127.0.0.1:4568:30
	// tcp://127.0.0.1:4569:40
	// )
	// 
	public function formatWeightArr() {
		array_walk($this->map,function($item,$key){
				$t = explode(':',str_replace('tcp://', '', $item));
				$this->map[$key]= array(
					'r'=>"tcp://$t[0]:$t[1]",
					'wht'=>$t[2]
				);
		});	
		return $this;
	}

	//获取总权重
	public function getWeightSum()
	{
		$len = $this->len;
		while ($len-- ) {
			$wht = $this->map[$len]['wht'];
			$this->ws += $wht;
		}
		return $this;
	}

	//获取权重区间
	public function getWeightInterval() {
		$total = 0;
		$this->flag = false;
	    foreach ($this->map as $key=>$item) {
			$total += $item['wht'];
			$this->map[$key]['range'] = $total;
		}
		return $this;
	}

	public function compute() {
	   if ($this->flag) 
	  	 	return $this->mftn();
	   else
	  	 	return $this->interval();
	}
	//方大法
	private function mftn() {
	    $_map = array();
	  	foreach ($this->map as $item) {
		  	$weight = $item['wht'];
		  	while ($weight--) {
		  	     $_map[] = $item;
		  	}
	  	}
	  	$key = rand(1,$this->ws);
	  	return $_map[$key - 1]['r'];
	}
	//区间法
	private function interval() {
		$key = rand(1, $this->ws);
		foreach ($this->map as $item) {
			if ($item['range'] >= $key) {
				return $item['r'];
			}
		}
	}
}