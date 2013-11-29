<?php
namespace MeroPhp\Logger;

class DebugBar extends \DebugBar\StandardDebugBar
{
	private $bDebugMode;
	private $aPrioritiesMapping = array();
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function log($nPriority, $sMessage)
	{
		// log to debug-bar
		$sType = $nPriority;
		if(isset($this->aPrioritiesMapping[$nPriority])){
			$sType = $this->aPrioritiesMapping[$nPriority];
		}
	    
		$this['messages']->$sType($sMessage);
	}
	
	public function startMeasure($sKey, $sTitle)
	{
		$this['time']->startMeasure($sKey, $sTitle);
	}
	
	public function stopMeasure($sKey)
	{
		$this['time']->stopMeasure($sKey);
	}
	
	public function exception($oEx)
	{
		$this['exceptions']->addException($oEx);
	}
	
	public function setBaseUrl($sBaseUrl)
	{
		$this->getJavascriptRenderer()->setBaseUrl($sBaseUrl);
	}
	
	public function getDebugHtml()
	{
		$oDebugbarRenderer = $this->getJavascriptRenderer();
		return array(
			$oDebugbarRenderer->renderHead(),
			$oDebugbarRenderer->render()
		);
	}
	
	public function addDatabase($oDatabase)
	{
		$oPdo = new \DebugBar\DataCollector\PDO\TraceablePDO($oDatabase);
		$this->addCollector(new \DebugBar\DataCollector\PDO\PDOCollector($oPdo));
	}
	
	public function setPriorityMapping(array $aPrioritiesMapping)
	{
		$this->aPrioritiesMapping = $aPrioritiesMapping;
	}
}
