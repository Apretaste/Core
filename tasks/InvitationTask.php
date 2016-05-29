<?php

/**
 * Invitation task
 * 
 * @author kuma
 * @version 1.0
 */
class invitationTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart  = time();
		$connection = new Connection();
		$email = new Email();
		$service = new Service();
		$service->showAds = true;
		$render = new Render();
		$response = new Response();
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];
		$log = "";

		// people who were invited but never used Apretaste
		$invitedPeople = $connection->deepQuery("
			SELECT invitation_time, email_inviter, email_invited
			FROM invitations 
			WHERE used=0 
			AND DATEDIFF(CURRENT_DATE, invitation_time) > 15 
			AND email_invited NOT IN (SELECT DISTINCT email from delivery_dropped)
			AND email_invited NOT IN (SELECT DISTINCT email from remarketing)
			ORDER BY invitation_time DESC
			LIMIT 450");

		// send the first remarketing
		$log .= "\nINVITATIONS (".count($invitedPeople).")\n";
		foreach ($invitedPeople as $person)
		{
			// check number of days since the invitation was sent 
			$datediff = time() - strtotime($person->invitation_time);
			$daysSinceInvitation = floor($datediff/(60*60*24));

			// validate old invitations to avoid bounces
			if($daysSinceInvitation > 60)
			{
				// re-validate the email
				$res = $utils->deepValidateEmail($person->email_invited);

				// if response not ok or temporal, delete from invitations list
				if($res[0] != "ok" && $res[0] != "temporal")
				{
					$connection->deepQuery("DELETE FROM invitations WHERE email_invited = '{$person->email_invited}'");
					$log .= "\t --skiping {$person->email_invited}\n";
					continue;
				}
			}

			// send data to the template
			$content = array(
				"date"=>$person->invitation_time,
				"inviter"=>$person->email_inviter,
				"invited"=>$person->email_invited,
				"expires"=>strtotime('next month')
			);

			// create html response
			$response->createFromTemplate('pendinginvitation.tpl', $content);
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send the invitation email
			$subject = "Su amigo {$person->email_inviter} esta esperando por usted!";
			$email->sendEmail($person->email_invited, $subject, $html);

			// insert into remarketing table
			$connection->deepQuery("INSERT INTO remarketing(email, type) VALUES ('{$person->email_invited}', 'INVITE')");

			// display notifications
			$log .= "\t{$person->email_invited}\n";
		}

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// printing log
		$log .= "EXECUTION TIME: $timeDiff seconds\n\n";
		echo $log;

		// saving the log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/remarketing_invitation.log");
		$logger->log($log);
		$logger->close();

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='invitation'");
	}
}
