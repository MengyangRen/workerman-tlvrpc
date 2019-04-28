<?php

class TestCilThread extends Thread {
	public $store;
	public $result;
	public $cC;
	public function __construct($cC) {
	    $this->cC = $cC;
	    $this->result = 0;
	}
    public function run() { 
		$client = stream_socket_client(
			'tcp://127.0.0.1:5000', 
			$errno, $errmsg,
			0.1,
			 STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT
	    );
	    $zip = file_get_contents("./mpclient.zip");
		fwrite($client, $zip."\n");
		fwrite($client,"<EOF>\n");
        $buf = fread($client, 1024);
        if (!empty($buf)) 
        	$this->result = 1;
        print "#".$this->cC."read >".$buf.PHP_EOL; 
        fclose($client);
    }
    public function isS() {
    	return $this->result;
    }
}

$j = $i = 500000;
$sucNum = 0;
$time_start = microtime(true);
$sample = 0;
while($j--)
{
    if($j == $i)
    {
        $sample = 1;
        $time_s = microtime(true);
    }

	$TestCilThread = new TestCilThread($j);
	$TestCilThread->start();
	$sucNum += $TestCilThread->isS();
    if($sample == 1) 
    {
        $sample = 0;
        echo (microtime(true) - $time_s)*1000, "\n" ;
    }
}
$end_start = microtime(true);
print "[QPS]".ceil($i/($end_start-$time_start))."QPS\n";
print "[平均请求处理时间]".($end_start-$time_start)/$i."\n";
print '[耗时]:'.round($end_start-$time_start,3).'秒'.PHP_EOL;
print '[成功执行]:'.$sucNum.PHP_EOL;

