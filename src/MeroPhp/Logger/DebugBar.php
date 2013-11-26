<?php
namespace MeroPhp\Logger;

class DebugBar extends \DebugBar\StandardDebugBar
{
	private $bDebugMode;
	private $aPrioritiesMapping = array();
	
	public function __construct($bDebugMode)
	{
		parent::__construct();
		$this->bDebugMode = $bDebugMode;
	}
	
	public function log($nPriority, $sMessage)
	{
		if(!$this->bDebugMode) return false;
		
		// log to debug-bar
		$sType = $nPriority;
		if(isset($this->aPrioritiesMapping[$nPriority])){
			$sType = $this->aPrioritiesMapping[$nPriority];
		}
				
		$this['messages']->$sType($sMessage);
	}
	
	public function startMeasure($sKey, $sTitle)
	{
		// if debug-is-disabled
		if(!$this->bDebugMode) return false;
		
		$this['time']->startMeasure($sKey, $sTitle);
	}
	
	public function stopMeasure($sKey)
	{
		// if debug-is-disabled
		if(!$this->bDebugMode) return false;
		
		$this['time']->stopMeasure($sKey);
	}
	
	public function exception($oEx)
	{
		// if debug-is-disabled
		if(!$this->bDebugMode) return false;
	
		$this['exceptions']->addException($oEx);
	}
	
	public function setBaseUrl($sBaseUrl)
	{
		$this->getJavascriptRenderer()->setBaseUrl($sBaseUrl);
	}
	
	public function getDebugHtml()
	{
		// if debug-is-disabled
		if(!$this->bDebugMode) return array('', '');
		
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
