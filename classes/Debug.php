<?php

/**
 * Debugger utility
 *
 * @author kumahackaer
 */
class Debug {

	/**
	 * Readable exception
	 * 
	 * @param Exception $e
	 * @return string
	 */
	static function getReadableException(Exception $e)
	{
		return "EXCEPTION: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()} \n {$e->getTraceAsString()}";
	}
}