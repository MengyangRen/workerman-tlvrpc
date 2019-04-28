<?php
namespace Libs;
use Libs\Logger;


/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  标准输入输出
 * @author  v.r
 * @package PhpRpc.Core.Std
 * 
 */


class Std 
{
	public $stdoutFile = "/tmp/prs-stdout-file.log"; 
	public static function __callStatic($method,$arg) {
    	if (empty($method)) {
			return false;
		}
		if (ENV !== DEV) { 
			Logger::init();
			Logger::$method($arg[0],$arg[1]);
			return true;
		}

		$attr = trim(ucfirst($method));
		$type = Logger::$$attr;
		$outStream = static::outStream($type);
		$msg = Logger::formatMessage(Logger::$logLevel[strtolower($method)],$arg[0]);
		static::safeOut($outStream,$msg);
	}

	//关闭标准输出->定向输出到文件
    public static function reset()
    {
        global $STDOUT, $STDERR;
        $handle = fopen(static::$stdoutFile, "a");
        if ($handle) {
            unset($handle);
            set_error_handler(function(){});
            fclose($STDOUT);
            fclose($STDERR);
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen(static::$stdoutFile, "a");
            $STDERR = fopen(static::$stdoutFile, "a");
            restore_error_handler();
        } else {
            throw new Exception('can not open stdoutFile ' . static::$stdoutFile);
        }
    }

	public static function safeOut($outStream,$msg) {
		fwrite($outStream,$msg);
  		fclose($outStream);
	}

	private static function outStream($type) {
		if(!posix_ttyname(STDOUT)) {
			@fclose(STDOUT);
			@fclose(STDERR); 
        	return fopen('/dev/null',"rw+");
        } else {
        	if ($type !== Logger::$Error)
        		return fopen('php://stdin',"rw+");
        	else 
        		return fopen("php://stderr", "rw+");
        }
	}
} 
