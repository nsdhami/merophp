<?php
namespace MeroPhp\Logger;

use HelloPlus\Logger\LoggerInterface;

class HpLoggerHandler implements LoggerInterface
{	
	// LEVEL: Based on RFC 5424 [http://tools.ietf.org/html/rfc5424]
	// 0: Emergency: system is unusable
	// 1: Alert: action must be taken immediately
	// 2: Critical: critical conditions
	// 3: Error: error conditions
	// 4: Warning: warning conditions
	// 5: Notice: normal but significant condition
	// 6: Informational: informational messages
	// 7: Debug: debug-level messages
	
	// Detailed debug information
	const LEVEL_DEBUG = 'debug';
	
	// Examples: User logs in, SQL logs.
	const LEVEL_INFO = 'info';
	
	// Uncommon events
	const LEVEL_NOTICE = 'notice';
	
	// Exceptional occurrences that are not errors.
	// Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
	const LEVEL_WARNING = 'warning';
	
	// Runtime errors that do not require immediate action but should typically be logged and monitored.
	const LEVEL_ERROR = 'error';
	
	// Critical conditions. Example: Application component unavailable, unexpected exception.
	const LEVEL_CRITICAL = 'critical';
	
	// Action must be taken immediately.
	// Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
	const LEVEL_ALERT = 'alert';
	
	// Emergency: system is unusable
	const LEVEL_EMERGENCY = 'emergency';
	
	private $nPriority;
	private $oDebugBar;
	
	private $sMailPriority;
	private $sErrorSubject;
	private $sReportErrorTo;
	private $bIsCaughtByHandler;
	
	public function __construct($nPriority)
	{
		$this->nPriority = $nPriority;
		$this->aMailConfig = array();
		$this->bIsCaughtByHandler = false;
	}
	
	public function addRecord($sPriority, $message, array $extra = array())
	{
		$sMessage = $message;
		if(is_array($message) || is_object($message)){
			$sMessage = print_r($message, true);
		}
		$this->log($sPriority, $message);
		
		if(!empty($this->oDebugBar)){
			$this->oDebugBar->log($sPriority, $sMessage);
		}
	}
	
	private function log($sPriority, $sMessage)
	{
		// if from exception, additional information is already logged
		if(! $this->bIsCaughtByHandler){
			$nErrorIndex = 3;
			$aTrace = debug_backtrace();
			$sMessage .= ' '. (isset($aTrace[$nErrorIndex]['class']) ? $aTrace[$nErrorIndex]['class'] : '') . 
						 '->'. (isset($aTrace[$nErrorIndex]['function']) ? $aTrace[$nErrorIndex]['function'] .'()' : '') . 
						 ' on line '. (isset($aTrace[$nErrorIndex-1]['line']) ? $aTrace[$nErrorIndex-1]['line'] : '') .
						 ' in '. (isset($aTrace[$nErrorIndex-1]['file']) ? $aTrace[$nErrorIndex-1]['file'] : '');
		}
		$this->bIsCaughtByHandler = false;
		
		// log extra detail and mail flag
		$bSendMail = false;
		if($sPriority == self::LEVEL_ERROR
		  || $sPriority == self::LEVEL_CRITICAL
		  || $sPriority == self::LEVEL_ALERT
		  || $sPriority == self::LEVEL_EMERGENCY){
			$bSendMail = true;
			$sMessage .= PHP_EOL . PHP_EOL .
						'URL: '. (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'na') . PHP_EOL . 
						'IP: '. (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'na') . PHP_EOL .
						'HttpMethod: '. (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'na') . PHP_EOL .
						'Referrer: '. (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'na');
		}
		error_log("[$sPriority] $sMessage");
		
		if($bSendMail){
			$this->mail($sPriority, $sMessage);
		}
		return $sMessage;
	}
	
	public function emerg($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_EMERGENCY, $message, $extra);
	}
	
	public function alert($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_ALERT, $message, $extra);
	}
	
	public function crit($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_CRITICAL, $message, $extra);
	}
	
	public function err($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_ERROR, $message, $extra);
	}
	
	public function warn($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_WARNING, $message, $extra);
	}
	
	public function notice($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_NOTICE, $message, $extra);
	}
	
	public function info($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_INFO, $message, $extra);
	}
	
	public function debug($message, array $extra = array())
	{
		return $this->addRecord(self::LEVEL_DEBUG, $message, $extra);
	}
	
	public function exception(\Exception $oEx)
	{
		$sMessage = $oEx->getCode() .': '. $oEx->getMessage();
		if($oEx instanceof \HelloPlus\Exception\Core){
			// TODO: GET THE EXCEPTION-LEVEL & proceed accordinly
			$sMessage .= PHP_EOL . $oEx->getDetail();
		}
		$this->addRecord(self::LEVEL_ERROR, $sMessage);
		$this->oDebugBar->exception($oEx);
	}
	
	public function emergencyplus($sMessage, $aExtra = array())
	{
		$this->addRecord(self::LEVEL_EMERGENCY, $sMessage, $aExtra);
		ErrorHandler::emergency($sMessage);
	}
	
	private function mail($sPriority, $sMessage)
	{
		$sSubject = $this->sErrorSubject .' - '. $sPriority; //mb_convert_encoding($this->sErrorSubject, "sjis", "utf-8");
		//$sMessage = mb_convert_encoding($sMessage, "sjis", "utf-8");
		return mail($this->sReportErrorTo, $sSubject, $sMessage);
	}
	
	public function registerErrorHandler()
	{
		// http://www.php.net/manual/en/errorfunc.constants.php
		set_error_handler(function($nErrno, $sErrstr, $sErrfile, $nErrline)
    	{
	    	$sPriority = self::LEVEL_DEBUG;
	    	switch ($nErrno){
	    		case E_ERROR:
	    		case E_RECOVERABLE_ERROR:
		    		$sPriority = self::LEVEL_ALERT;
		    		break;
	    		case E_WARNING:
	    		case E_DEPRECATED:
		    		$sPriority = self::LEVEL_CRITICAL;
		    		break;
	    		case E_NOTICE:
		    		$sPriority = self::LEVEL_NOTICE;
		    		break;
	    		case E_PARSE:
	    		case E_CORE_ERROR:
	    		case E_CORE_WARNING:
	    		case E_COMPILE_ERROR:
	    		case E_COMPILE_WARNING:
		    		$sPriority = self::LEVEL_ERROR;
		    		break;
	    		case E_STRICT:
	    		case E_USER_ERROR:
	    		case E_USER_WARNING:
	    		case E_USER_NOTICE:
	    		case E_USER_DEPRECATED:
		    		$sPriority = self::LEVEL_WARNING;
	    			break;
	    		case E_ALL:
		    		$sPriority = self::LEVEL_DEBUG;
		    		break;
	    	}
			
	    	$this->bIsCaughtByHandler = true;
	        $this->addRecord($sPriority, "ErrorHandler => $sErrstr (on $nErrline at $sErrfile)");
		});
	}
    
    public function registerExceptionHandler()
    {
    	set_exception_handler(function(\Exception $oEx)
	    {
	    	$this->bIsCaughtByHandler = true;
	    	
	    	$nErrline = $oEx->getLine();
	    	$sErrfile = $oEx->getFile();
	    	$sErrstr = $oEx->getCode() .': '. $oEx->getMessage();
	        $this->addRecord(self::LEVEL_ERROR, "ExceptionHandler => $sErrstr (on $nErrline at $sErrfile)");
	    });
    }
    
	public function setDebugBar(DebugBar $oDebugBar)
	{
		$this->oDebugBar = $oDebugBar;
	}
	
	public function getDebugBar()
	{
		return $this->oDebugBar;
	}
	
	public function setMail($sReportErrorTo, $sErrorSubject, $sMailPriority = self::LEVEL_ERROR)
	{
		$this->sMailPriority = $sMailPriority;
		$this->sErrorSubject = $sErrorSubject;
		$this->sReportErrorTo = $sReportErrorTo;
	}
}
