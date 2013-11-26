<?php
namespace MeroPhp\Logger;

class ErrorHandler 
{
	public static function emergency($sMessage, $aExtra = array())
	{
		//parent::log(self::EMERG, $sMessage, $aExtra);
		header("Location: /server_error.html");
		exit;
	}
}
