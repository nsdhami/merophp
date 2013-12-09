<?php
namespace Users\Controller\Exception;

class DatabaseException extends \Exception
{
    protected $extra;
    
    public function __construct($message = null, $code = null, $previous = null)
    {
        $this->sendMail = false;
        parent::__construct($message, $code, $previous);
    }
    
    public function getExtra()
    {
        return $this->extra;
    }
    
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }
}
