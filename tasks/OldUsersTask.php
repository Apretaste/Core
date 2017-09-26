<?php

class OldUsersTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		echo "[INFO] Deactivating old users...\n";
		$connection = new Connection();
		$connection->query("UPDATE person SET active = 0 WHERE datediff(CURRENT_TIMESTAMP, last_access) > 30;");
	}
}