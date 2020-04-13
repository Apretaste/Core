<?php

// mark as innactive users who has been more than 45 days without using the app

class DeactivateTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// start counting time
		$timeStart = time();

		// deactivating old users
		Connection::query("UPDATE person SET active=0 WHERE DATEDIFF(CURRENT_TIMESTAMP, last_access) > 45");

		// save the status in the database
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='deactivate'");
	}
}
