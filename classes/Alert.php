<?php

class Alert
{
	/**
	 * Create an alert and notify the alert group
	 *
	 * @author salvipascual
	 * @param String $text
	 * @param Enum $severity: WARNING,NOTICE,URGENT
	 * */
	public function createAlert($text, $severity="WARNING")
	{
		// get the group from the configs file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$email = $di->get('config')['global']['alert_email'];

		// get the details of the alert
		$date = date('l jS \of F Y h:i:s A');
		$subject = "$severity: $text";
		$body = "A new alert has been created in Apretaste.<br/>$text<br/>$date";

		// send email alert to the alerts group
		$sender = new Email();
		$sender->sendEmail($email, $subject, $body);

		// @TODO send SMS alert
		// @TODO save into alerts database
	}
}
