<?php

$t1 = microtime(true);
$phArr = array();
for ($i = 0; $i < 10000; $i++) {
	print chr(3); 
    print chr(8); 
	$cmd = "php ".dirname(__FILE__)."/client.php";
	$phArr[$i] = popen($cmd, 'w');
}

$errNum = 0;
foreach ($phArr as $ph) {
	if ($ph === FALSE) 
       $errNum ++;
	if (is_resource($ph))
		pclose($ph);
}

$t2 = microtime(true);
print '[耗时]:'.round($t2-$t1,3).'秒'.PHP_EOL;
print '[失败执行]:'.$errNum.PHP_EOL;