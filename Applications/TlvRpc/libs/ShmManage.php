<?php
namespace Libs;
/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  共享内存管理
 *  
 * @author  v.r
 * @package PhpRpc.Core.ShmManage
 *
 *  需支持（sysshm）
 * 
 */

class ShmManage {

    public static $IPC_KEY; //因为这个IPC_KEY需要为整型crc32('20161101133600') | $IPC_KEY;
    public static $SEQ_KEY;// 共享内存中存储序列号ID的KEY  int类型

    //信号量
    public $semIdFd; 
    //共享内存
    public $shmIdFd; 

	public function __construct($IPC_KEY,$SEQ_KEY = 8) {
		$this->__check();
	    if (empty($IPC_KEY)) 
	    	static::$IPC_KEY = ftok(__FILE__, 'R');
	    else
	    	static::$IPC_KEY = $IPC_KEY;
	    static::$SEQ_KEY =  $SEQ_KEY;
	} 

	private function __check(){
		if (!extension_loaded('sysvshm'))
			throw new \Exception("See the documentation for installing shared memory extensions
				(https://www.php.net/manual/zh/sem.installation.php)");
	}


	public function OpenShm() {
        // 创建或关联一个现有的，以"1234"为KEY的共享内存
        $this->shmIdFd = shm_attach(static::$IPC_KEY, 10000);   
        return $this;
	}

	public function isExistShmVal($key) {
		return shm_has_var($this->shmIdFd,$key);
	}

	public function getShmVar($key) {
       return shm_get_var($this->shmIdFd,$key);
	}


	public function getSem() {
     	$this->semIdFd = sem_get(static::$IPC_KEY);

	}

	public static function createKey($type = 1 ) {
		if ($type == 1)
			return fileinode(__FILE__);
		if ($type == 2)
			return ftok(__FILE__, 'R');
		if ($type == 3)
			return 4780233;
	}

}

