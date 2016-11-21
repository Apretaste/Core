<?php

/**
 * Get all people who did not use Apretaste in a while
 *
 * If you've missed 30-60 days, send a remarketing telling what you missed
 * If you've missed 61-90 days, send a remarketing and tell them they will be excluded
 * If you've missed more than 91 days, exclude them
 * */

class NewemailTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$connection = new Connection();
		$email = new Email();
		$service = new Service();
		$service->showAds = false;
		$render = new Render();
		$response = new Response();
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];

		// get valid people
		$people = $connection->deepQuery("
			SELECT email, username, first_name, last_access
			FROM person
			WHERE active=1
			AND email not in (SELECT DISTINCT email FROM delivery_dropped)
			AND DATE(last_access) > DATE('2016-05-01')
			AND email like '%.cu'
			AND email not like '%@mms.cubacel.cu'");

		// send the remarketing
		$log = "";
		foreach ($people as $person)
		{
			// get the email address
			$newEmail = "apretaste+{$person->username}@gmail.com";

			// create the variabels to pass to the template
			$content = array("newemail"=>$newEmail, "name"=>$person->first_name);

			// create html response
			$response->setEmailLayout("email_simple.tpl");
			$response->createFromTemplate('newEmail.tpl', $content);
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send the email
			$email->sendEmail($person->email, "Sorteando las dificultades, un email lleno de alegria", $html);

			$log .= $person->email . "\n";
		}

		// saving the log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/newemail.log");
		$logger->log($log);
		$logger->close();
	}
}
