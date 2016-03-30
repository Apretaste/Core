<?php

/**
 * Get all people who did not use Apretaste in a while
 * 
 * If you've missed 30-60 days, send a reminder telling what you missed
 * If you've missed 61-90 days, send a reminder and tell them they will be excluded
 * If you've missed more than 91 days, exclude them
 * */

class ReminderTask extends \Phalcon\Cli\Task
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


		// FIRST REMINDER

		// get the list of people missed for the last 30 days
		$sql = "SELECT email, last_access FROM person WHERE active=1 AND IFNULL(DATEDIFF(CURRENT_DATE, last_access),99) > 30 AND reminder=0 LIMIT 100";
		$firstReminderPeople = $connetion->deepQuery($sql);

		// send the first reminder
		echo "\nFIRST REMINDER (" . count($firstReminderPeople) . ")\n";
		foreach ($firstReminderPeople as $key=>$person)
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

			// send email to the $person->email
			$email->sendEmail($person->email, "Se le extranna por Apretaste", $html, $images);

			// move reminder to the next state
			$connetion->deepQuery("UPDATE person SET reminder=1 WHERE email='{$person->email}'");

			// display notifications
			echo $key ."/". count($firstReminderPeople) . ": {$person->email} \n";
		}


		// SECOND REMINDER

		// get the list of people missed for the last 30 days
		$sql = "SELECT email, last_access FROM person WHERE active=1 AND IFNULL(DATEDIFF(CURRENT_DATE, last_access),99) > 60 AND reminder=1 LIMIT 100";
		$secondReminderPeople = $connetion->deepQuery($sql);

		// send the first reminder
		echo "\n\nSECOND REMINDER (" . count($secondReminderPeople) . ")\n";
		foreach ($secondReminderPeople as $key=>$person)
		{
			// get services that changed since last time
			$sql = "SELECT * FROM service WHERE insertion_date BETWEEN '{$person->last_access}' AND CURRENT_TIMESTAMP AND listed=1";
			$services = $connetion->deepQuery($sql);

			// create html response
			$response->createFromTemplate('remindme2.tpl', array("services"=>$services));
			$response->internal = true;
			$html = $render->renderHTML($service, $response);

			// send email to the $person->email
			$email->sendEmail($person->email, "Hace rato no le veo", $html);

			// move reminder to the next state
			$connetion->deepQuery("UPDATE person SET reminder=2 WHERE email='{$person->email}'");

			// display notifications
			echo $key ."/". count($secondReminderPeople) . ": {$person->email} \n";
		}


		// THIRD REMINDER

		// get the list of people missed for the last 30 days
		$sql = "SELECT email FROM person WHERE active=1 AND IFNULL(DATEDIFF(CURRENT_DATE, last_access),99) > 90 AND reminder=2";
		$thirdReminderPeople = $connetion->deepQuery($sql);

		// send the first reminder
		echo "\n\nUNSUSCRIBING (" . count($thirdReminderPeople) . ")\n";
		foreach ($thirdReminderPeople as $key=>$person)
		{
			// unsubscribe person
			$utils->unsubscribeFromEmailList($person->email);

			// move reminder to the next state
			$connetion->deepQuery("UPDATE person SET active=0, reminder=3 WHERE email='{$person->email}'");

			// display notifications
			echo $key ."/". count($thirdReminderPeople) . ": {$person->email} \n";
		}

		// finish message
		echo "\n\nDONE\n";
	}
}
