<?php
namespace MeroPhp\Logger;

class IntrospectionProcessor
{
	/**
	* @param array $record
	* @return array
	*/
	public function __invoke(array $record)
	{
		$trace = debug_backtrace();
		$i = 5; // based on the level of depth
		
		// we should have the call source now
		$record['extra'] = array_merge(
			$record['extra'],
			array(
				'class'    => isset($trace[$i]['class']) ? $trace[$i]['class'] : null,
				'file'     => isset($trace[$i-1]['file']) ? $trace[$i-1]['file'] : null,
				'line'     => isset($trace[$i-1]['line']) ? $trace[$i-1]['line'] : null,
				'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
			)
		);
		
		return $record;
	}
}
