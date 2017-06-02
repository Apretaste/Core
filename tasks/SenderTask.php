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
			ORDER BY inserted ASC");

		// remove duplicated entries from array
		$ommit = array();
		foreach ($unsent as $a){
			if(in_array($a->id, $ommit)) continue;
			foreach ($unsent as $b){
				if($a->id != $b->id && $a->user == $b->user && $a->subject == $b->subject){
					$ommit[] = $b->id;
					$key = array_search($b, $unsent);
					unset($unsent[$key]);
				}
			}
		}

		// remove duplicated entries from the database
		if($ommit){
			$ids = implode("','", $ommit);
			$connection->query("UPDATE delivery_received SET status='block' WHERE id IN ('$ids')");
		}

		// get only the first 5 emails to send
		$unsent = array_slice($unsent, 0, 5);
		print_r($unsent); exit;

		echo "SENDING ".count($unsent)." EMAILS\n";

		// create global objects
		$render = new Render();
		$utils = new Utils();

		// loop the list and re-send emails
		foreach ($unsent as $u)
		{
			echo "\tPROCESING EMAIL TO:{$u->user} WITH ID:{$u->id}\n";

			// run the request and get the service and responses
			try{
				$attachEmail = explode(",", $u->attachments);
				$ret = $utils->runRequest($u->user, $u->subject, $u->body, $attachEmail);
				$service = $ret->service;
				$responses = $ret->responses;
			} catch (Exception $e) {
				error_log("SENDER ERROR:" . $e->getMessage());
				echo $e->getMessage();
				continue;
			}

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
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='sender'");
	}
}
