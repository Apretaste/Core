<?php

class OldUsersTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$connection = new Connection();
		$condition  = "datediff(CURRENT_TIMESTAMP, last_access) > 30";
		$sql        = "SELECT count(*) as total FROM person WHERE active = 1 AND $condition;";
		$r          = $connection->query($sql);

		$total = $r[0]->total * 1;

		if($total > 0)
		{
			echo "[INFO] Deactivating old users...\n";
			$sql = "UPDATE person SET active = 0 WHERE $condition;";
			$connection->query($sql);
			echo "[DONE] $total old users deactivated.\n";
		}
		else
			echo "[INFO] No old users.\n";
	}
}