<?php

use Phalcon\Mvc\Controller;

class WebhookController extends Controller
{
	public function droppedAction()
	{
		// do not allow empty calls
		if(empty($_POST)) die("EMPTY CALL");

		// get the params from post
		$email = $_POST['recipient'];
		$domain = $_POST['domain'];
		$reason = $_POST['reason'];
		$code = isset($_POST['code']) ? $_POST['code'] : "";
		$desc = isset($_POST['description']) ? str_replace("'", "", $_POST['description']) : "";

		// do not save Spam as hardfail
		if (stripos($desc, 'spam') !== false) $reason = "spam";

		$connection = new Connection();

		// mark as bounced if the email is part of the latest campaign
		$campaign = $connection->deepQuery("
			SELECT id
			FROM (SELECT id, emails FROM campaign WHERE status='SENT' ORDER BY sending_date DESC LIMIT 1) A
			WHERE A.emails like '%$email%'");
		if( ! empty($campaign))
		{
			// increase the bounce number for the campaign
			$connection->deepQuery("UPDATE campaign SET	bounced=bounced+1 WHERE id={$res[0]->id}");

			// unsubscribe from the list
			$utils = new Utils();
			$utils->unsubscribeFromEmailList($email);
		}

		// save into the database
		$sql = "INSERT INTO delivery_dropped(email,sender,reason,code,description) VALUES ('$email','$domain','$reason','$code','$desc')";
		$connection->deepQuery($sql);

		// echo completion message
		echo "FINISHED";
	}
}
