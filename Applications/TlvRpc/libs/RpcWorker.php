<?php
namespace Libs;

use Protocols\Tlv;
use Protocols\JsonResponse;

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  sevicers-worker
 *  
 * @author  v.r
 * @package PhpRpc.Core.RpcWorker
 *
 *  需支持（sysshm）
 * 
 */

class RpcWorker {

	protected $services = array();
	protected $laod;

	public function __construct($services = null) {
		$this->services = $services;
	}

	public function process($request) {
		$method = $request->method;
		$key = null;
		if (is_array($method)) {
			list($key) = $method;
		} else if (is_string($method)) {
			$key = $method;
		}

		$result = null;

		try {
			if (!isset($this->services[$key])) {
				Std::Warning("Unkown method {@$request->method}",__CLASS__);
				throw new \Exception("Unkown method {@$request->method}");			
			}
			$method = array($this->services[$key],$method[1]);
			$result = call_user_func_array($method, $request->params);
			$result = new JsonResponse($result,array($key,$method[1]),null, $request->traceId);
			return $result;
		} catch (\Exception $e) {
			$code = $e->getCode() ? $e->getCode() : 500;
			throw new \Exception($e->getMessage(), $code);
			//$result = new JsonResponse(null,array($key,$method[1]),$e->getMessage(), $request->traceId);
			//return $result;
		}
	}

	public function addService($service) {
		$key = $service;
		if (false !== strpos($service,'\\')) 
			list($key) = array_reverse(explode('\\',$service));
		if (is_object($service)) {
			$this->services[get_class($service)] = $service;
		} else if (class_exists($service)) {
			$this->services[$key] = new $service;
		} else if (function_exists($service)) {
			$this->services[$key] = $service;
		}
	}
	
	public function addServices(array $services = NULL) {
		if (empty($services) && !empty($this->load))
			$services = $this->load;
	/*		Std::warning(
				"Dude, the addition of services can't be empty",
				__CLASS__
			);*/
		foreach ($services as $service) {
			$this->addService($service);
		}
		unset($this->load); 

		return true;
	}

	public function load() {
	   require_once SERVICE_BOOTSTRAP;
        $this->load = array_filter(array_unique(
        	array_map(function($class) {
       	    if (false !== strpos($class,'\\') && 
       	    	false !== strpos($class,'Service') && 
       	    	false !== strpos($class,'Src'))
       	    	return $class;
       },get_declared_classes())));
        return $this;
	} 
}
