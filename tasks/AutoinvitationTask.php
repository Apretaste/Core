<?php

/**
 * Auto-invitations task
 * 
 * @author kuma
 * @version 1.0
 */

class autoinvitationTask extends \Phalcon\Cli\Task
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

		// people in the list to be automatically invited
		$people = $connection->deepQuery("
			SELECT * FROM autoinvitations
			WHERE email NOT IN (SELECT email FROM person)
			AND email NOT IN (SELECT DISTINCT email FROM delivery_dropped)
			AND email NOT IN (SELECT DISTINCT email from remarketing)
			AND error=0
			LIMIT 450");

		// send the first remarketing
		$log .= "\nAUTOMATIC INVITATIONS (".count($people).")\n";
		foreach ($people as $person)
		{
			// if response not ok, check the email as error
			$res = $utils->deepValidateEmail($person->email);
			if($res[0] != "ok")
			{
				$connection->deepQuery("UPDATE autoinvitations SET error=1, processed=CURRENT_TIMESTAMP WHERE email='{$person->email}'");
				$log .= "\t --skiping {$person->email}\n";
				continue;
			}

			// create html response
			$content = array("email" => $person->email);
			$response->createFromTemplate('autoinvitation.tpl', $content);
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send invitation email
			$subject = "Dos problemas, y una solucion";
			$email->sendEmail($person->email, $subject, $html);

			// mark as sent
			$connection->deepQuery("
				START TRANSACTION;
				DELETE FROM autoinvitations WHERE email='{$person->email}';
				INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'AUTOINVITE');
				COMMIT;");

			// display notifications
			$log .= "\t{$person->email}\n";
		}
		
		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// printing log
		$log .= "EXECUTION TIME: $timeDiff seconds\n\n";
		echo $log;

		// saving the log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/remarketing_autoinvitation.log");
		$logger->log($log);
		$logger->close();

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='autoinvitation'");
	}
}
