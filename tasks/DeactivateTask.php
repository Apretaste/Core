<?php

class DeactivateTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// start counting time
		$timeStart = time();

		// deactivating old users
		$connection = new Connection();
		$connection->query("UPDATE person SET active=0 WHERE DATEDIFF(CURRENT_TIMESTAMP, last_access) > 45");

		// save the status in the database
		$timeDiff = time() - $timeStart;
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='deactivate'");
	}
}
