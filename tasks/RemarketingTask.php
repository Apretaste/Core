<?php

/**
 * Get all people who did not use Apretaste in a while
 * 
 * If you've missed 30-60 days, send a remarketing telling what you missed
 * If you've missed 61-90 days, send a remarketing and tell them they will be excluded
 * If you've missed more than 91 days, exclude them
 * */

class remarketingTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$connetion = new Connection();
		$email = new Email();
		$service = new Service();
		$service->showAds = true;
		$render = new Render();
		$response = new Response();
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];
		$timeStart  = time();


		// FIRST remarketing


		// people missed for the last 30 days and with no remarketing emails unopened
		$sql = "
			SELECT email, last_access 
			FROM person 
			WHERE active=1 
			AND IFNULL(DATEDIFF(CURRENT_DATE, last_access),99) > 30 
			AND email not in (SELECT DISTINCT email FROM remarketing WHERE opened IS NULL)
			LIMIT 100";
		$firstremarketingPeople = $connetion->deepQuery($sql);

		// send the first remarketing
		$log = "\nFIRST REMINDER (".count($firstremarketingPeople).")\n";
		foreach ($firstremarketingPeople as $person)
		{
			// get services that changed since last time
			$sql = "SELECT * FROM service WHERE insertion_date BETWEEN '{$person->last_access}' AND CURRENT_TIMESTAMP AND listed=1";
			$services = $connetion->deepQuery($sql);

			// create the variabels to pass to the template
			$content = array("services"=>$services);
			$images = array("$wwwroot/public/images/missyou.jpg");

			// create html response
			$response->createFromTemplate('remindme1.tpl', $content, $images);
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// move remarketing to the next state and add $1 to his/her account
			$email->sendEmail($person->email, "Se le extranna por Apretaste", $html, $images);

			// move remarketing to the next state and add +1 credits
			$connetion->deepQuery("
				START TRANSACTION;
				UPDATE person SET credit=credit+1 WHERE email='{$person->email}';
				INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'REMINDER1');
				COMMIT;");

			// display notifications
			$log .= "\t{$person->email}\n";
		}


		// SECOND remarketing


		// people with REMINDER1 unaswered for the last 30 days, and without REMINDER2 created
		$sql = "
			SELECT email
			FROM remarketing A
			WHERE type='REMINDER1'
			AND opened IS NULL
			AND DATEDIFF(CURRENT_DATE, sent) > 30
			AND (SELECT COUNT(email) FROM remarketing WHERE type='REMINDER2' AND opened IS NULL AND email=A.email)=0";
		$secondremarketingPeople = $connetion->deepQuery($sql);

		// send the first remarketing
		$log .= "SECOND REMINDER (".count($secondremarketingPeople).")\n";
		foreach ($secondremarketingPeople as $person)
		{
			// create html response
			$response->createFromTemplate('remindme2.tpl', array());
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send email to the $person->email
			$email->sendEmail($person->email, "Hace rato no le veo", $html);

			// move remarketing to the next state and add +1 credits
			$connetion->deepQuery("
				START TRANSACTION;
				UPDATE person SET credit=credit+1 WHERE email='{$person->email}';
				INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'REMINDER2');
				COMMIT;");

			// display notifications
			$log .= "\t{$person->email}\n";
		}


		// Exclude people who did not answer


		// people with REMINDER2 unaswered, sent 30 days ago and not EXCLUDED 
		$sql = "
			SELECT email
			FROM remarketing A
			WHERE type='REMINDER2'
			AND opened IS NULL
			AND DATEDIFF(CURRENT_DATE, sent) > 30
			AND (SELECT COUNT(email) from remarketing WHERE type='EXCLUDED' AND opened IS NULL AND email=A.email)=0";
		$thirdremarketingPeople = $connetion->deepQuery($sql);

		// send the first remarketing
		$log .= "UNSUSCRIBING (".count($thirdremarketingPeople).")\n";
		foreach ($thirdremarketingPeople as $person)
		{
			// unsubscribe person
			$utils->unsubscribeFromEmailList($person->email);

			// move remarketing to the next state and unsubscribe
			$connetion->deepQuery("
				START TRANSACTION;
				UPDATE person SET active=0 WHERE email='{$person->email}';
				INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'EXCLUDED');
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
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/remarketing.log");
		$logger->log($log);
		$logger->close();

		// save the status in the database
		$connetion->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='remarketing'");
	}
}
