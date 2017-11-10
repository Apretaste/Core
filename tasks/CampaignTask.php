<?php

class CampaignTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart = time();
		$failure = 0;

		// get the first campaign waiting to be sent
		$connection = new Connection();
		$campaign = $connection->query("SELECT * FROM campaign WHERE status = 'WAITING' ORDER BY inserted DESC LIMIT 1");

		// check if there are not campaigns
		if (empty($campaign)) return;
		else $campaign = $campaign[0];

		// update campaign as SENDING
		$connection->query("UPDATE campaign SET status='SENDING' WHERE id={$campaign->id}");

		// get the receipients
		$people = $connection->query("
			SELECT A.email, B.pass
			FROM person A JOIN authentication B
			ON A.email = B.email
			WHERE A.mail_list = 1
			AND B.appname = 'apretaste'
			AND B.pass IS NOT NULL
			AND A.email NOT IN (SELECT email FROM campaign_processed WHERE campaign={$campaign->id})");

		// prepare the email
		$email = new Email();
		$email->subject = $campaign->subject;
		$email->body = $campaign->content;

		// show initial message
		$total = count($people);
		echo "\nSTARTING COUNT: $total\n";

		// email people one by one
		$counter = 1;
		foreach ($people as $person)
		{
			// show personal counter
			echo "$counter/$total - {$person->email}\n";
			$counter++;

			// send the email
			$email->to = $person->email;
			$res = $email->sendEmailViaWebmail();

			// save as fail if there are issues sending
			if($res->code == "200") {$status="SENT"; $cnt="sent=sent+1"; }
			else {$status="FAILED"; $cnt="failed=failed+1"; $failure++;}

			// save status before moving to the next email
			$connection->query("
				INSERT INTO campaign_processed (email, campaign, status) VALUES ('{$person->email}', {$campaign->id}, '$status');
				UPDATE campaign SET $cnt WHERE id='{$campaign->id}'");

			// stop execution if 5% failure
			if(($failure*100)/$total > 5)
			{
				// update the campaign as error
				$connection->query("UPDATE campaign SET status='ERROR' WHERE id='{$campaign->id}'");

				// save log
				$utils = new Utils();
				$utils->createAlert("Campaign with ID {$campaign->id} is having failure percent too high", "ERROR");
				die($msg);
			}
		}

		// set the campaign as SENT
		$connection->query("UPDATE campaign SET status='SENT' WHERE id='{$campaign->id}'");

		// save the status in the database
		$timeDiff = time() - $timeStart;
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='campaign'");
	}
}
