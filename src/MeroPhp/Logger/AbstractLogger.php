<?php
namespace MeroPhp\Logger;

abstract class AbstractLogger
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
    
    protected static $LOG_LEVEL_MAPS = array(
        self::LEVEL_EMERGENCY => 0,
        self::LEVEL_ALERT     => 1,
        self::LEVEL_CRITICAL  => 2,
        self::LEVEL_ERROR     => 3,
        self::LEVEL_WARNING   => 4,
        self::LEVEL_NOTICE    => 5,
        self::LEVEL_INFO      => 6,
        self::LEVEL_DEBUG     => 7
    );
    
    protected $debugBar;
    
    protected $logLevel;
    protected $emergencyUrl;
    
    protected $mailTo;
    protected $mailLevel;
    protected $mailSubject;
    
    protected $bIsCaughtByHandler;
    
    protected function __construct()
    {
        $this->bIsCaughtByHandler = false;
        $this->logLevel = self::$LOG_LEVEL_MAPS[self::LEVEL_DEBUG];
        $this->mailLevel = self::$LOG_LEVEL_MAPS[self::LEVEL_WARNING];
    }
    
    public function addRecord($logLevel, $message)
    {
        if(self::$LOG_LEVEL_MAPS[$logLevel] > $this->logLevel){
            return;
        }
        
        if(is_array($message) || is_object($message)){
            $message = print_r($message, true);
        }
        $this->log($logLevel, $message);
        
        if(! empty($this->debugBar)){
            $this->debugBar->log($logLevel, $message);
        }
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
        $sMessage = $oEx->getCode() . ': ' . $oEx->getMessage();
        $this->addRecord(self::LEVEL_ERROR, $sMessage);
        
        if(! empty($this->debugBar)){
            $this->debugBar->exception($oEx);
        }
    }
    
    public function registerErrorHandler()
    {
        // http://www.php.net/manual/en/errorfunc.constants.php
        set_error_handler(function ($nErrno, $sErrstr, $sErrfile, $nErrline)
        {
            $sPriority = self::LEVEL_DEBUG;
            switch($nErrno){
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
        set_exception_handler(function (\Exception $oEx)
        {
            $this->bIsCaughtByHandler = true;
    
            $nErrline = $oEx->getLine();
            $sErrfile = $oEx->getFile();
            $sErrstr = $oEx->getCode() . ': ' . $oEx->getMessage();
            $this->addRecord(self::LEVEL_ERROR, "ExceptionHandler => $sErrstr (on $nErrline at $sErrfile)");
        });
    }
    
    public function emergencyplus($sMessage, $aExtra = array())
    {
        $this->addRecord(self::LEVEL_EMERGENCY, $sMessage, $aExtra);
        header("Location: $this->emergencyUrl");
        exit();
    }
    
    protected function mail($message, $priority = 0)
    {
        $subject = $this->mailSubject . ' - ' . $priority;
        $message = mb_convert_encoding($message, "utf-8", "utf-8");
        $headers  = 'Content-type: text/plain; charset=utf-8' ."\r\n";
        return mail($this->mailTo, $subject, $message, $headers);
    }
    
    public function setMail($mailTo, $mailSubject, $mailLevel)
    {
        $this->mailTo = $mailTo;
        $this->mailLevel = $mailLevel;
        $this->mailSubject = $mailSubject;
    }
    
    public function setEmergencyUrl($emergencyUrl)
    {
        $this->emergencyUrl = $emergencyUrl;
    }
    
    public function setDebugBar(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }
    
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }
    
    public function getDebugBar()
    {
        return $this->debugBar;
    }
    
    abstract public function log($logLevel, $message);
}
