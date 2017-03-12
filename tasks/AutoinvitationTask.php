<?php

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
			AND processed IS NULL
			AND email LIKE '%@nauta.cu'
			LIMIT 500");

		// send the first remarketing
		$log .= "\nAUTOMATIC INVITATIONS (".count($people).")\n";
		foreach ($people as $person)
		{
			// prepare variables
			$subject = "Dos problemas, y una solucion";
			$content = array("email" => $person->email);

			// create html response
			$response->setResponseEmail($person->email);
			$response->setResponseSubject($subject);
			$response->setEmailLayout("email_simple.tpl");
			$response->createFromTemplate('autoinvitation.tpl', array());
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send invitation email
			$email->sendEmail($person->email, $subject, $html);

			// mark as sent
			$connection->deepQuery("UPDATE autoinvitations SET processed=CURRENT_TIMESTAMP WHERE email='{$person->email}'");

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
