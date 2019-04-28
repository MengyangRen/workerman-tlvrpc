<?php
namespace Libs;
/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  基于文件的互斥锁实现
 *  
 * @author  v.r
 * @package PhpRpc.Core.Locker
 *
 * 参考使用（PHP sync模块中的Mutex）
 * 
 */

interface Locker {
	//获取锁
	public function lock();	
	//释放锁
	public function unlock();
} 

class MutexLocker implements Locker {
	public $lock;	
	public $tryNum;
	public function __construct($file = null,$tryNum = 3 ) {
	    if (empty($file)) 
	    	$this->lock = fopen(__FILE__, 'r');
	    $this->tryNum = $tryNum;

	} 
	public function lock() {
		if (!$this->lock) 
			return false;
		$Cc = $this->tryNum;
		while ($Cc--) {
			//以不阻塞的方式获取写锁
			$lock = flock($this->lock, LOCK_EX | LOCK_NB);
			if ($lock) return true;
		}
	    return false;
	}
	public function unlock() {
		if(!$this->lock)
			return false;
		return flock($this->lock, LOCK_UN);
	}
}