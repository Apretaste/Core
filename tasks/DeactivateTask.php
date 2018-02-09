<?php

class DeactivateTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// Deactivating old users
		$connection = new Connection();
		$connection->query("UPDATE person SET active=0 WHERE datediff(CURRENT_TIMESTAMP, last_access) > 30;");

		// save the status in the database
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP WHERE task='deactivate'");
	}
}
