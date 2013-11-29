<?php
namespace MeroPhp\Logger;

class MonoLogger //extends \Monolog\Logger implements LoggerInterface
{	
	private $nPriority;
	private $oDebugBar;
	
	public function __construct($nPriority)
	{
		parent::__construct('app');
		$this->nPriority = $nPriority;
	}
	
	public function addRecord($sPriority, $message, array $extra = array())
	{
		$sMessage = $message;
		if(is_array($message) || is_object($message)){
			$sMessage = print_r($message, true);
		}
		parent::addRecord($sPriority, $sMessage, $extra);
		
		if(isset($this->oDebugBar)){
			$this->oDebugBar->log($sPriority, $sMessage);
		}
	}
	
	public function emerg($message, array $extra = array())
	{
		return $this->addRecord(parent::EMERGENCY, $message, $extra);
	}
	
	public function alert($message, array $extra = array())
	{
		return $this->addRecord(parent::ALERT, $message, $extra);
	}
	
	public function crit($message, array $extra = array())
	{
		return $this->addRecord(parent::CRITICAL, $message, $extra);
	}
	
	public function err($message, array $extra = array())
	{
		return $this->addRecord(parent::ERROR, $message, $extra);
	}
	
	public function warn($message, array $extra = array())
	{
		return $this->addRecord(parent::WARNING, $message, $extra);
	}
	
	public function notice($message, array $extra = array())
	{
		return $this->addRecord(parent::NOTICE, $message, $extra);
	}
	
	public function info($message, array $extra = array())
	{
		return $this->addRecord(parent::INFO, $message, $extra);
	}
	
	public function debug($message, array $extra = array())
	{
		return $this->addRecord(parent::DEBUG, $message, $extra);
	}
	
	public function exception(\Exception $oEx)
	{
		$sMessage = $oEx->getCode() .': '. $oEx->getMessage();
		if(method_exists($oEx, 'getDetail')){
			$sMessage .= PHP_EOL . $oEx->getDetail();
		}
		$this->addRecord(parent::WARNING, $sMessage);
		$this->oDebugBar->exception($oEx);
	}
	
	public function setDebugBar(DebugBar $oDebugBar)
	{
		$this->oDebugBar = $oDebugBar;
		$this->oDebugBar->setPriorityMapping(array(
	        parent::DEBUG => 'debug',
	        parent::INFO => 'info',
	        parent::NOTICE => 'notice',
	        parent::WARNING => 'warning',
	        parent::ERROR => 'error',
	        parent::CRITICAL => 'criticial',
	        parent::ALERT => 'alert',
	        parent::EMERGENCY => 'emergency'
    	));
	}
	
	public function getDebugBar()
	{
		return $this->oDebugBar;
	}
	
	public function emergencyplus($sMessage, $aExtra = array())
	{
		$this->addRecord(parent::EMERGENCY, $sMessage, $aExtra);
		header("Location: $this->emergencyUrl");
	}
}
