<?php

/**
 * Send email campaigns to the users
 *
 * @author salvipascual
 * @version 1.0
 */
class CampaignTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart  = time();
		$utils = new Utils();
		$connection = new Connection();
		$sender = new Email();

		// get the first campaign created that is waiting to be sent
		$campaign = $connection->deepQuery("
			SELECT id, subject, content
			FROM campaign
			WHERE sending_date < CURRENT_TIMESTAMP
			AND status = 'WAITING'
			GROUP BY sending_date ASC
			LIMIT 1");

		// check if there are not campaigns
		if (empty($campaign)) return;
		else $campaign = $campaign[0];

		// check campaign as SENDING
		$connection->deepQuery("UPDATE campaign SET status='SENDING' WHERE id = {$campaign->id}");

		// get the list of people in the list who hsa not receive this campaign yet
		// so in case the campaign fails when it tries again starts from the same place
		$people = $connection->deepQuery("
			SELECT email FROM person
			WHERE mail_list=1 AND active=1
			AND email NOT IN (SELECT email FROM campaign_sent WHERE campaign={$campaign->id})");

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

			// replace the template variables
			$content = $utils->campaignReplaceTemplateVariables($person->email, $campaign->content, $campaign->id);

            // parse campaign content
			$render = new Render();
			$service = new Service('campaign');
            $response = new Reseponse();

            // TODO: add more data
            $data = [
                'campaign' => $campaign,
                'user' => $person,
                'counter' => $counter,
                'total' => $total
            ];

            $response->createFromTemplate($content, $data);
            $content = $render->renderHTML($service, $response);

			// send test email
			$sender->trackCampaign = $campaign->id;
			$result = $sender->sendEmail($person->email, $campaign->subject, $content);

			// add to bounced and unsubscribe if there are issues sending
			$bounced = "";
			$status = "SENT";
			if( ! $result)
			{
				$utils->unsubscribeFromEmailList($person->email);
				$bounced = "bounced=bounced+1,";
				$status = "BOUNCED";
			}

			// save status before moving to the next email
			$connection->deepQuery("
				INSERT INTO campaign_sent (email, campaign, status) VALUES ('{$person->email}', '{$campaign->id}', '$status');
				UPDATE campaign SET $bounced sent=sent+1 WHERE id='{$campaign->id}'");
		}

		// set the campaign as SENT
		$connection->deepQuery("UPDATE campaign SET status='SENT' WHERE id='{$campaign->id}'");

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// saving the log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/campaigns.log");
		$logger->log("ID: {$campaign->id}, RUNTIME: $timeDiff, SUBJECT: {$campaign->subject}");
		$logger->close();

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='campaign'");
	}
}
