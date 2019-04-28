<?php
namespace Libs;

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  日志类-Psr规范
 *  
 * @author  v.r
 * @package PhpRpc.Core.Logger
 * 
 */

class Logger {

    public static $Info = 1;
    public static $Error = 2;
    public static $Warning = 3;
    public static $Debug = 4;

    public static $logLevel = array(
        'debug'   => 'debug',
        'info'    => 'info',
        'notice'  => 'notice',
        'warning' => 'warning',
        'error'   => 'error',
    );  
    
    public static $defaultPermission = 0777;
    public static $logDir = '/tmp/log';
    public static $enable_task_log = false;

    public static function formatMessage($level, $message)
    {
        $level = strtoupper($level);
        $time = date('Y-m-d H:i:s');
        return "[{$time}] [{$level}] {$message}" . PHP_EOL;
    }
    public static function __callStatic($method,$arg) {
        if (empty($method))
            return false;

        $level = strtolower($method);
        if (!in_array($level,self::$logLevel))
            return false;

        $log     = $arg[0]; 
        $logName = $arg[1];

        return self::log($log, $logName,$level);

    }

    private static function log($log, $logName, $logLevel) {
        if (empty(self::$logDir)) {
            if (!self::init()) {
                return false;
            }
        }

        if (empty(self::$logLevel) || !in_array($logLevel, self::$logLevel)) {
            return false;
        }

        if (!isset($logName)) {
            return false;
        }

        $dir = self::$logDir . '/bak_' . date('Ymd');
        $dirArr = explode('/', trim($dir));
        $tmpDir = '';
        $faDir = '';

        for ($i = 1; $i < count($dirArr); $i++) {
            $faDir .= '/' . $dirArr[$i - 1];
            $tmpDir .= '/' . $dirArr[$i];
            if (!is_dir($tmpDir)) {
                if (!is_writeable($faDir)) {
                    return false;
                }
                $res = mkdir($tmpDir, self::$defaultPermission, true);
                if (!$res) {
                    return false;
                }
            }
        }
        if (strpos($logName,'\\')) 
            $logName = str_replace('\\', '_', $logName);
        $file = $dir . '/' . $logName . '.log';
        error_log(self::formatMessage($logLevel, $log), 3, $dir . '/' . $logName . '.log');
        return true;
    }

    public static function init() {
        self::$logDir = RPC_LOG_PATH;
        return true;
    }
}