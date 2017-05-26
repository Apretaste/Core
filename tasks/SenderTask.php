<?php

// re-send emails that failed in the past
// @author salvipascual

class SenderTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$timeStart = time();

		// get the number failures with less than 3 tries
		$connection = new Connection();
		$unsent = $connection->query("
			SELECT * FROM delivery_received
			WHERE tries < 3
			AND ((`status` = 'new' AND TIMESTAMPDIFF(MINUTE, inserted, NOW()) > 5)
			OR `status` = 'error')
			LIMIT 5");

		echo "SENDING ".count($unsent)." EMAILS\n";

		// create global objects
		$render = new Render();
		$utils = new Utils();

		// loop the list and re-send emails
		foreach ($unsent as $u)
		{
			echo "\tPROCESING EMAIL TO:{$u->user}\n";

			// run the request and get the service and responses
			$attachEmail = explode(",", $u->attachments);
			$ret = $utils->runRequest($u->user, $u->subject, $u->body, $attachEmail);
			$service = $ret->service;
			$responses = $ret->responses;

			// create the new Email object
			$email = new Email();
			$email->id = $u->id;
			$email->to = $u->user;
			$email->replyId = $u->messageid;
			$email->group = $service->group;

			// get params for the email and send the response emails
			foreach($responses as $rs)
			{
				// prepare and send the email
				if($rs->email) $email->to = $rs->email;
				$email->subject = $rs->subject;
				$email->images = $rs->images;
				$email->attachments = $rs->attachments;
				$email->body = $render->renderHTML($service, $rs);
				$email->send();
			}
		}

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		echo "FINISHED IN $timeDiff SECONDS\n";

		// save the status in the database
		$connection = new Connection();
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='sender'");
	}
}
