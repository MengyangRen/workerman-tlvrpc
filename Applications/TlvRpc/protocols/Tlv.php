<?php
namespace Protocols;

use Protocols\ProtocolInterface;
use Protocols\TlvFactory;

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  Tlv传输协议实现
 *  
 * @author  v.r
 * @package PhpRpc.protocol.ProtocolInterface
 *
 * 
 *
 * 
 * LTV协议-Pack
 * 标签（Tag），长度域（Length），内容域（Value）
 *
 * 数据结构 {
 *    Tag(命令)
 *    len (长度)
 *    body
 *  }
 *
 * Network:<
 * 03e8
 * 0000010b
 * {"method":["ExampleService","hello"],"params":[1000],"version":null,"user":"test","password":"7e04d8576a59833a1dd9e04ad7a27c23","timestamp":1556129969.776097,"secret":"{1BA09530-F9E6-478D-9965-7EB31A59537E}","signature":"b171134924f61ae98a5d968e94d3106a","traceId":1}
 * 
 *  >
 * 
 */


class Tlv implements ProtocolInterface {
	//CONST RECEIVE_DONE  = 'done'; 
    CONST TAG_RPC_CLIENT_SEND       = 1000;
    CONST TAG_RPC_CLIENT_RECV       = 2000;
    CONST TAG_RPC_SERVER_RECV       = 3000;
    CONST TAG_RPC_SERVER_SEND       = 4000;
    /**
     * 检查包的完整性
     * 如果能够得到包长，则返回包的在buffer中的长度，否则返回0继续等待数据
     * @param string $buffer
     */
    public static function input($buffer) { return (new TlvFactory($buffer))->check();}
     
    /**
     * 打包，当向客户端发送数据的时候会自动调用
     * @param string $buffer
     * @return string
     */
    public static function decode($buffer) { return (new TlvFactory($buffer))->analyse();}

    /**
     * 解包，当接收到的数据字节数等于input返回的值（大于0的值）自动调用
     * 并传递给onMessage回调函数的$data参数
     * @param string $buffer
     * @return string
     */
    public static function encode($data) { return (new TlvFactory($data))->build();}

}

class TlvFactory {
	public $tag;
    public $length;
    public $body;
    CONST TAG_BYTE      = 2;  // tag  2byte 
    CONST LENGTH_BYTE   = 4;  // len  4byte 
    CONST HEAD_LEN      = 6;  // head = tag + len 
    public $source;

    public function __construct($data = null) {
    	if (is_array($data)) {
    		$this->source['tag'] =  $data[0];
    		$this->source['body'] = $data[1];
    	} else {
    		$this->source = trim($data);
    	}
    }
    
    public function check() {
		if (strlen($this->source) < static::HEAD_LEN) 
		    return static::HEAD_LEN;
		$headFildes = $this->splitFileds(
        	$this->source,
        	[static::TAG_BYTE*2,static::LENGTH_BYTE*2]
        );
        $bodyLen = hexdec($headFildes[1]); 
        if (!is_numeric($bodyLen))
        	return false;

        if (strlen($this->source) < ($bodyLen + static::HEAD_LEN * 2))
        	return (static::HEAD_LEN*2 + $bodyLen) - strlen($this->source);

        return strlen($this->source);
        //return Tlv::RECEIVE_DONE;
    }

    public function analyse() {
        $fields = $this->splitFileds(
            $this->source,
            [static::TAG_BYTE*2,static::LENGTH_BYTE*2,strlen($this->source)]
        );
		$analyse = array_merge(
			    array_map(
				    function($val){
				    	return hexdec($val);
				    }, 
					array(
						'tag'   =>$fields[0],
						'length'=>$fields[1]
					)
			    ),
				unpack('a*body',$fields[2])
		);	
        return  JsonRequest::decode($analyse['body']);
    }

    public function build() {
       $this->tag = bin2hex(pack('n',$this->source['tag']));
	   $this->length = bin2hex(pack('N',strlen($this->source['body'])));
	   $this->body = pack('a*',$this->source['body']);
	   return $this->tag.$this->length.$this->body."\n";
    }
    
    public function splitFileds($bin,array $fieldLengthes) {
	    $len = count($fieldLengthes);
	    $start = 0;
	    $fieldArray = array();
	    for ($i = 0; $i < $len; $i++) { 
	        $fieldArray[$i] = substr($bin,$start,$fieldLengthes[$i]);
	        $start += $fieldLengthes[$i];
	    }
	    return $fieldArray;
    }
}


interface ProtocolInterface
{
    public static function input($recv_buffer);
    public static function decode($recv_buffer);
    public static function encode($data);
}


class Json {
    public static function encode($message) {
        return json_encode($message);
    }
    public static function decode($message) {
        return json_decode($message);
    }
    public function __toJson() {
        return self::encode($this);
    }
}

class JsonResponse  extends Json {
    public $method;
    public $params;
    public $timestamp;
    public $result;
    public $error;
    public $traceId;
    public function __construct($result,$method,$error, $traceId = null) {
        $this->result = $result;
        $this->method = $method; 
        $this->timestamp =  microtime(true);
        $this->error = $error;
        $this->traceId = $traceId;
    }
}

class JsonRequest extends Json {
    public $method;
    public $params;
    public $version;
    public $user = 'test';
    public $password;
    public $timestamp;
    public $secret = '{9BS09530-F9E6-476D-7777-7EB31A59537E}';
    public $signature;
    public $traceId;
    const NEXTID = -1;
    public function __construct($method, $params, $traceId = null) {
        $this->method = $method;
        $this->params = $params;
        $this->password = md5($this->user.':'.$this->secret);
        $this->timestamp =  microtime(true);
        $this->traceId = $traceId == self::NEXTID ? self::nextId() : $traceId;
    }
    public function encrypt($JsonRequest,$secret,$rt = true) {
        if ($rt) {
            $this->signature = md5($JsonRequest.$secret);
            return $this;
        } else { return md5($JsonRequest.$secret);}
    }
    public static function nextId() {
        static $id = 0;
        return ++$id;
    }
}