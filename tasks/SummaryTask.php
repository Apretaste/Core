<?php

class SummaryTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// start counting time
		$timeStart = time();
		$currentMonth = date('Y-m');
		$currentMonthDay = "$currentMonth-01";

		//
		// MONTHLY GROSS TRAFFIC
		//

		// get the number for current month
		$monthlyGrossTraffic = Connection::query("
			SELECT COUNT(id) AS total
			FROM delivery
			WHERE request_date >= '$currentMonthDay'")[0]->total;

		// save or update the total
		Connection::query("
			INSERT INTO summary (label, dated, value)
			VALUES ('monthly_gross_traffic', '$currentMonth', $monthlyGrossTraffic)
			ON DUPLICATE KEY UPDATE value = $monthlyGrossTraffic, inserted = CURRENT_TIMESTAMP");

		//
		// MONTHLY UNIQUE TRAFFIC
		//

		// get the number for current month
		$monthlyUniqueTraffic = Connection::query("
			SELECT COUNT(DISTINCT user) AS total
			FROM delivery
			WHERE request_date >= '$currentMonthDay'")[0]->total;

		// save or update the total
		Connection::query("
			INSERT INTO summary (label, dated, value)
			VALUES ('monthly_unique_traffic', '$currentMonth', $monthlyUniqueTraffic)
			ON DUPLICATE KEY UPDATE value = $monthlyUniqueTraffic, inserted = CURRENT_TIMESTAMP");

		//
		// save the status in the database
		//
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='summary'");
	}
}