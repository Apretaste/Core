<?php

class OnlineTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// set what users are currently online
		$connection = new Connection();
		$connection->query("
			START TRANSACTION;
			UPDATE person SET `online`=0
			UPDATE person SET `online`=1 WHERE TIMESTAMPDIFF(MINUTE,last_access,NOW()) < 20
			COMMIT;");

		// save the status in the database
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP WHERE task='online'");
	}
}
