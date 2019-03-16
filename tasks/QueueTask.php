<?php

/**
 * Sends an email that was previosly queued
 * @author salvipascual
 */

class QueueTask extends \Phalcon\Cli\Task
{
	public function mainAction($args)
	{
		$timeStart = time();
		if(empty($args[0])) return false;

		// get the data to construct the email
		$id = $args[0];
		$data = Connection::query("SELECT data FROM delivery_queue WHERE id = $id");

		// stop if id is invalid
		if(empty($data)) return false;
		else $data = $data[0]->data;

		// unserialize data and construct the email 
		$email = unserialize(base64_decode($data)); 

		// send the email
		$res = $email->send();

		// delete from the table if email was sent
		if($res->code == "200") {
			Connection::query("DELETE FROM delivery_queue WHERE id = $id");
		}

		// save the status in the database
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='queue'");
	}
}