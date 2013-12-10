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
        if(! $this->isCaughtByHandler){
            $nErrorIndex = 3;
            $trace = debug_backtrace();
            $message .= ' ' . 
                        (isset($trace[$nErrorIndex]['class']) ? $trace[$nErrorIndex]['class'] : '') . '->' . 
                        (isset($trace[$nErrorIndex]['function']) ? $trace[$nErrorIndex]['function'] . '()' : '') . ' on line ' . 
                        (isset($trace[$nErrorIndex - 1]['line']) ? $trace[$nErrorIndex - 1]['line'] : '') . ' in ' . 
                        (isset($trace[$nErrorIndex - 1]['file']) ? $trace[$nErrorIndex - 1]['file'] : '');
        }
        $this->isCaughtByHandler = false;
        
        // log extra detail and mail flag
        $sendMail = false;
        if(self::$LOG_LEVEL_MAPS[$logLevel] <= self::$LOG_LEVEL_MAPS[self::LEVEL_ERROR]){
            $sendMail = true;
            $url = 'http'. (($_SERVER['SERVER_PORT'] == 443) ? 's' : '') .'://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $message .= "\n\nURL: $url\nIP: ". 
                        (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'na') . PHP_EOL . 'HttpMethod: ' . 
                        (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'na') . PHP_EOL . 'Referrer: ' . 
                        (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'na');
        }
        error_log("[$logLevel] $message");
        
        if($sendMail){
            $this->mail($message, $logLevel);
        }
        return $message;
    }
}
