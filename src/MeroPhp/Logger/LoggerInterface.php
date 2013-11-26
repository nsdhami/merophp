<?php
namespace MeroPhp\Logger;

interface LoggerInterface 
{
	public function getDebugBar();
	public function setDebugBar(DebugBar $oDebugBar);
	public function exception(\Exception $oEx);
	public function emergencyplus($sMessage, $aExtra = array());
}
