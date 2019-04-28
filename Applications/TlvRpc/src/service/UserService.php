<?php
namespace Src\Service;

class  UserService {
	public $list = [
	      1000 =>['uname'=>'vicnetn.ren','age'=>27,'desc'=>'有点小狂战。。'],
	      2000 =>['uname'=>'漳卅','age'=>27,'desc'=>'有点小狂战。。'],
	      3000 =>['uname'=>'李四','age'=>27,'desc'=>'有点小狂战。。']
	];
	public function hello($v) {
		return 'UserService.hello'.$v;
	}
	public function getInfoByUid($uid = 1000) {
		$list = [];
		$cC = 2;
		while ($cC--) {
			$list[] = $this->list[$uid];

		}
	    return $list;
	}
}