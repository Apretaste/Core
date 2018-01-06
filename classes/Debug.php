<?php

class Debug {

	static function getReadableException(Exception $e)
	{
		return "EXCEPTION: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()} \n {$e->getTraceAsString()}";
	}
}