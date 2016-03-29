<?php

/**
 * Get all people who did not use Apretaste in a while
 * 
 * If you've missed 30-60 days, send a reminder telling what you missed
 * If you've missed 61-90 days, send a reminder and tell them they will be excluded
 * If you've missed more than 91 days, exclude them
 * */

class ReminderTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$connetion = new Connection();

		// get all people
		$sql = "SELECT email FROM person WHERE active = 1";
		$people = $connetion->deepQuery($sql);

		foreach ($people as $key=>$person)
		{
			// get the number last date
			$sql = "SELECT MAX(request_time) as last_access FROM utilization WHERE requestor = '{$person->email}'";
			$res = $connetion->deepQuery($sql)[0];
			$lastAccess = empty($res->last_access) ? 'NULL' : "'{$res->last_access}'";

			// update last access
			$sql = "UPDATE person SET last_access=$lastAccess WHERE email = '{$person->email}'";
			$connetion->deepQuery($sql);

			echo $key ."/". count($people) . ": {$person->email} - $lastAccess \n";
		}
	}
}
