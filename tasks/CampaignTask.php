<?php

/**
 * Send email campaigns to the users
 *
 * @author salvipascual
 * @version 2.0
 */
class CampaignTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart = time();
		$utils = new Utils();
		$connection = new Connection();

		// get the first campaign created that is waiting to be sent
		$campaign = $connection->query("
			SELECT id, subject, content, list, `group`
			FROM campaign
			WHERE sending_date < CURRENT_TIMESTAMP
			AND status = 'WAITING'
			GROUP BY sending_date ASC
			LIMIT 1");

		// check if there are not campaigns
		if (empty($campaign)) return;
		else $campaign = $campaign[0];

		// set the default group to send emails
		$sender = new Email();
		$sender->setGroup($campaign->group);

		// update campaign as SENDING
		$connection->query("UPDATE campaign SET status='SENDING' WHERE id={$campaign->id}");

		// when we choose only mail list users
		if($campaign->list == "1")
		{
			$people = $connection->query("
				SELECT email, 'internal' as type
				FROM person WHERE mail_list=1 AND active=1
				AND email NOT IN (SELECT DISTINCT email FROM campaign_sent WHERE campaign={$campaign->id})
				AND email NOT IN (SELECT DISTINCT email FROM delivery_dropped)");
		}
		// when we choosse ALL Apretaste active users
		elseif($campaign->list == "2")
		{
			$people = $connection->query("
				SELECT email, 'internal' as type
				FROM person WHERE active=1
				AND email NOT IN (SELECT DISTINCT email FROM campaign_sent WHERE campaign={$campaign->id})
				AND email NOT IN (SELECT DISTINCT email FROM delivery_dropped)");
		}
		// all other lists
		else
		{
			// get the people
			$people = $connection->query("
				SELECT id, email, name, 'list' as type
				FROM campaign_subscribers
				WHERE list = '{$campaign->list}'
				AND status <> 'BOUNCED' AND status <> 'DISABLED'
				AND email NOT IN (SELECT DISTINCT email FROM campaign_sent WHERE campaign={$campaign->id})
				AND email NOT IN (SELECT DISTINCT email FROM delivery_dropped)");
		}

		// show initial message
		$total = count($people);
		echo "\nSTARTING COUNT: $total\n";

		// email people one by one
		$counter = 1;
		foreach ($people as $person)
		{
			// show message
			echo "$counter/$total - {$person->email}\n";
			$counter++;

			// create new response
			$response = new Response();
			$response->createFromHTML($campaign->content);

			// render the HTML
			$render = new Render();
			$service = new Service('campaign');
			$html = $render->renderHTML($service, $response);

			// send test email
			$result = $sender->sendEmail($person->email, $campaign->subject, $html);

			// add to bounced and unsubscribe if there are issues sending
			$bounced = ""; $status = "SENT";
			if( ! $result)
			{
				$utils->unsubscribeFromEmailList($person->email);
				$bounced = "bounced=bounced+1,";
				$status = "BOUNCED";
			}

			// save status before moving to the next email
			$sql = "INSERT INTO campaign_sent (email, campaign, `group`, status) VALUES ('{$person->email}', '{$campaign->id}', '{$campaign->group}', '$status');";
			$sql .= "UPDATE campaign SET $bounced sent=sent+1 WHERE id='{$campaign->id}';";
			if($person->type == "list") $sql .= "UPDATE campaign_subscribers SET sent=sent+1, status='$status' WHERE id='{$person->id}';";
			$connection->query($sql);
		}

		// set the campaign as SENT
		$connection->query("UPDATE campaign SET status='SENT' WHERE id='{$campaign->id}'");

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// saving the log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/campaigns.log");
		$logger->log("ID: {$campaign->id}, RUNTIME: $timeDiff, SUBJECT: {$campaign->subject}");
		$logger->close();

		// save the status in the database
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='campaign'");
	}
}
