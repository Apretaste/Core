<?php

// set what users are currently online

class OnlineTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// start counting time
		$timeStart = time();

		// set what users are currently online
		Connection::query("
			START TRANSACTION;
			UPDATE person SET `online`=0;
			UPDATE person SET `online`=1 WHERE TIMESTAMPDIFF(MINUTE,last_access,NOW()) < 20;
			COMMIT;");

		// save the status in the database
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='online'");
	}
}
