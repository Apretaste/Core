<?php

/**
* Create a notification for users with an old version of the app
 *
 * @author salvipascual
 * @version 1.0
 */
class VersionTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// inicialize supporting classes
		$timeStart = time();
		$utils = new Utils();

		// get the latest version of the app
		$lastestVersion = $this->di->get('config')['global']['appversion'];

		// get people using an old version
		$connection = new Connection();
		$people = $connection->query("
			SELECT email FROM person
			WHERE appversion < $lastestVersion
			AND appversion <> ''");

		// show initial message
		$total = count($people);
		echo "\nSTARTING COUNT: $total\n";

		// create notifications for the people
		$counter = 1;
		foreach ($people as $person)
		{
			// show message per person
			echo "$counter/$total - {$person->email}\n";
			$counter++;

			// create the notification
			$text = "Actualiza a la version $lastestVersion escribiendo a navegacuba@gmail.com";
			$utils->addNotification($person->email, "App", $text);
		}

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// save the status in the database
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='version'");
	}
}
