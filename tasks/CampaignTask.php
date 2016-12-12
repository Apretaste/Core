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

		// inicialize status variables
		$bounced = 0;
		$emails = array();

		// get the first campaign created that is waiting to be sent
		$campaign = $connection->deepQuery("
			SELECT *
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

		// get the list of people in the list and send emails
		$people = $connection->deepQuery("SELECT email FROM person WHERE mail_list=1 AND active=1");
		foreach ($people as $person)
		{
			// update the reference variables
			$emails[] = $person->email;

			// replace the template variables
			$content = $utils->campaignReplaceTemplateVariables($person->email, $campaign->content, $campaign->id);

			// send test email
			$sender->trackCampaign = $campaign->id;
			$result = $sender->sendEmail($person->email, $campaign->subject, $campaign->content);

			// add to bounced and unsubscribe if there are issues sending
			if( ! $result)
			{
				$utils->unsubscribeFromEmailList($person->email);
				$bounced++;
			}
		}

		// update the campaign with the status
		$sent = count($people);
		$emails = implode(",", $emails);
		$connection->deepQuery("UPDATE campaign SET status='SENT', sent='$sent', bounced='$bounced', emails='$emails' WHERE id={$campaign->id}");

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// saving the log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/campaigns.log");
		$logger->log("ID: {$campaign->id}, SUBJECT: {$campaign->subject}, SENT: $sent, BOUNCED: $bounced, RUNTIME: $timeDiff");
		$logger->close();

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='invitation'");
	}
}
