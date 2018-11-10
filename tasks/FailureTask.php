<?php

/**
 * Create a notification for users with an old version of the app
 *
 * @author kumahacker
 * @version 1.0
 */
class FailureTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// initialize supporting classes
		$timeStart = time();

		// calculate failure percentage
		$lastCodes = Connection::query("SELECT COUNT(*) AS total,delivery_code FROM delivery WHERE NOT delivery_code IS NULL AND TIMESTAMPDIFF(MINUTE,request_date,NOW())<15 GROUP BY delivery_code;");

		$sendCount = 0;
		$failuresCount = 0;
		foreach($lastCodes as $code)
		{
			if($code->delivery_code == '200')
			{
				$sendCount = $code->total;
			}
			else $failuresCount += $code->total;
		}

		$total = $sendCount + $failuresCount;

		// alert developers if failures are over 20%
		if(($failuresCount / $total) >= 0.2)
		{
			$text = "[RunController::runFailure] APP FAILURE OVER 20%: Users may not be receiving responses in the last 15 minutes";
			Utils::createAlert($text, "ERROR");
		}

		// save the status in the database
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='failure'");
	}
}
