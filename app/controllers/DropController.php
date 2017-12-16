<?php

use Phalcon\Mvc\Controller;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class DropController extends Controller
{
	/**
	 * To handle emails drops in Amazon
	 */
	public function indexAction()
	{
		// get the message object
		$message = Message::fromRawPostData();
		// error_log(print_r($message,true)); // subscription

		// validate the message
		$validator = new MessageValidator();
		if( ! $validator->isValid($message)) return false;

		// convert string into json object
		$message = json_decode($message['Message']);

		// accept only bounces
		if($message->notificationType != "Bounce") return false;

		// get the params from the message
		$email = $message->bounce->bouncedRecipients[0]->emailAddress;
		$message = $message->bounce->bouncedRecipients[0]->diagnosticCode;
		$message = str_replace(array("'", "\n"), "", $message);
		$code = intval(trim(explode(" ", $message)[1]));

		// save it into the database
		$connection = new Connection();
		$connection->query("
			INSERT INTO delivery_checked (email, status, code, message)
			VALUES ('$email', 'bounce', '$code', '$message')");
	}
}
