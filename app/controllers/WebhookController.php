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

		// mark as bounced if the email is part of the latest campaign
		$connection = new Connection();
		$campaign = $connection->deepQuery("
			SELECT campaign, email FROM (
				SELECT * FROM `campaign_sent`
				WHERE campaign = (SELECT id FROM campaign WHERE status='SENT' ORDER BY sending_date DESC LIMIT 1)
			) A WHERE email = '$email'");
		if(count($campaign)>0)
		{
			// increase the bounce number for the campaign
			$campaign = $campaign[0];
			$connection->deepQuery("
				UPDATE campaign SET	bounced=bounced+1 WHERE id={$campaign->campaign};
				UPDATE campaign_sent SET status='BOUNCED', date_opened=CURRENT_TIMESTAMP WHERE id={$campaign->campaign} AND email='{$email}'");

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
