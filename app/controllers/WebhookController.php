<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class WebhookController extends Controller
{
	/**
	 * To handle emails drops in Amazon
	 */
	public function droppedAmazonAction()
	{
		// get the message object
		$message = Message::fromRawPostData();

		// Validate the message
		$validator = new MessageValidator();
		if ($validator->isValid($message))
		{
			// save in the drops log
			$wwwroot = $this->di->get('path')['root'];
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/drops.log");
			$logger->log("AMAZON: ".print_r($message, true));
			$logger->close();

			// convert string into json object
			$message = json_decode($message['Message']);

			// accept only bounces
			if($message->notificationType != "Bounce") die("Not a bounce");

			// get the params from the message
			$email = $message->bounce->bouncedRecipients[0]->emailAddress;
			$domain = explode("@", $message->mail->source)[1];
			$reason = $message->bounce->bouncedRecipients[0]->action;
			$desc = $message->bounce->bouncedRecipients[0]->diagnosticCode;
			$code = explode(" ", $desc)[1];

			// treat the bounce
			$this->dropped($email, $domain, 'amazon', $reason, $code, $desc);
		}
	}

	/**
	 * To handle emails drops in Mailgun
	 */
	public function droppedAction()
	{
		// do not allow empty calls
		if(empty($_POST)) die("EMPTY CALL");

		// save in the drops log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/drops.log");
		$logger->log("MAILGUN: ".print_r($_POST, true));
		$logger->close();

		// get the params from post
		$email = $_POST['recipient'];
		$domain = $_POST['domain'];
		$reason = $_POST['reason'];
		$code = isset($_POST['code']) ? $_POST['code'] : "";
		$desc = isset($_POST['description']) ? str_replace("'", "", $_POST['description']) : "";

		// treat the bounce
		$this->dropped($email, $domain, 'mailgun', $reason, $code, $desc);
	}

	/**
	 * To handle emails drops
	 */
	private function dropped($email, $domain, $sender, $reason, $code, $desc)
	{
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

		// blacklist the user domain if it was censored
		if($code >= 500)
		{
			$userDomain = explode("@", $email)[1];
			$sql = "UPDATE domain SET blacklist = CONCAT(blacklist,',$userDomain') WHERE domain='$domain'";
			$connection->deepQuery($sql);
		}

		// save into the database
		$desc = str_replace("'", "", $desc);
		$desc = str_replace("\n", "", $desc);
		$sql = "INSERT INTO delivery_dropped(email,sender,company,reason,code,description) VALUES ('$email','$domain','$sender','$reason','$code','$desc')";
		$connection->deepQuery($sql);

		// echo completion message
		echo "FINISHED";
	}
}
