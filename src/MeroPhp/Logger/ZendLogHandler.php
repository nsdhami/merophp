<?php
namespace MeroPhp\Logger;

use Zend\Log\Logger;

class ZendLogHandler extends Logger implements LoggerInterface
{	
	private $nPriority;
	private $oDebugBar;
	
	public function __construct($nPriority)
	{
		parent::__construct();
		$this->nPriority = $nPriority;
	}
	
	public function log($nPriority, $sMessage, $aExtra = array())
	{
		parent::log($nPriority, $sMessage, $aExtra);
		$this->oDebugBar->log($nPriority, $sMessage);
	}
	
	public function emerg($message, $extra = array())
	{
		return $this->log(self::EMERG, $message, $extra);
	}
	
	public function alert($message, $extra = array())
	{
		return $this->log(self::ALERT, $message, $extra);
	}
	
	public function crit($message, $extra = array())
	{
		return $this->log(self::CRIT, $message, $extra);
	}
	
	public function err($message, $extra = array())
	{
		return $this->log(self::ERR, $message, $extra);
	}
	
	public function warn($message, $extra = array())
	{
		return $this->log(self::WARN, $message, $extra);
	}
	
	public function notice($message, $extra = array())
	{
		return $this->log(self::NOTICE, $message, $extra);
	}
	
	public function info($message, $extra = array())
	{
		return $this->log(self::INFO, $message, $extra);
	}
	
	public function debug($message, $extra = array())
	{
		return $this->log(self::DEBUG, $message, $extra);
	}
	
	public function exception(\Exception $oEx)
	{
		$this->log(self::WARN, $oEx->getMessage());
		$this->oDebugBar->exception($oEx);
	}
	
	public function emergencyplus($sMessage, $aExtra = array())
	{
		parent::log(self::EMERG, $sMessage, $aExtra);
		ErrorHandler::emergency($sMessage);
	}
	
	public function setDebugBar(DebugBar $oDebugBar)
	{
		$this->oDebugBar = $oDebugBar;
		$this->oDebugBar->setPriorityMapping(array(
			Logger::EMERG  => 'emergency',
			Logger::ALERT  => 'alert',
			Logger::CRIT   => 'critical',
			Logger::ERR    => 'error',
			Logger::WARN   => 'warning',
			Logger::NOTICE => 'notice',
			Logger::INFO   => 'info',
			Logger::DEBUG  => 'debug',
		));
	}
	
	public function getDebugBar()
	{
		return $this->oDebugBar;
	}
}
