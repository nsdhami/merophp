<?php
namespace MeroPhp\Logger;

class SimpleLogger extends AbstractLogger
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function log($logLevel, $message)
    {
        // if from exception, additional information is already logged
        if(! $this->bIsCaughtByHandler){
            $nErrorIndex = 3;
            $aTrace = debug_backtrace();
            $message .= ' ' . 
                        (isset($aTrace[$nErrorIndex]['class']) ? $aTrace[$nErrorIndex]['class'] : '') . '->' . 
                        (isset($aTrace[$nErrorIndex]['function']) ? $aTrace[$nErrorIndex]['function'] . '()' : '') . ' on line ' . 
                        (isset($aTrace[$nErrorIndex - 1]['line']) ? $aTrace[$nErrorIndex - 1]['line'] : '') . ' in ' . 
                        (isset($aTrace[$nErrorIndex - 1]['file']) ? $aTrace[$nErrorIndex - 1]['file'] : '');
        }
        $this->bIsCaughtByHandler = false;
        
        // log extra detail and mail flag
        $bSendMail = false;
        if(self::$LOG_LEVEL_MAPS[$logLevel] <= self::$LOG_LEVEL_MAPS[self::LEVEL_ERROR]){
            $bSendMail = true;
            $message .= PHP_EOL . PHP_EOL . 'URL: ' . 
                        (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'na') . PHP_EOL . 'IP: ' . 
                        (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'na') . PHP_EOL . 'HttpMethod: ' . 
                        (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'na') . PHP_EOL . 'Referrer: ' . 
                        (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'na');
        }
        error_log("[$logLevel] $message");
        
        if($bSendMail){
            $this->mail($message, $logLevel);
        }
        return $message;
    }
}
