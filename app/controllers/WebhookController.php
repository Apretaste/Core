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

		// validate the message
		$validator = new MessageValidator();
		if( ! $validator->isValid($message)) die("Invalid");

		// convert string into json object
		$message = json_decode($message['Message']);

		// accept only bounces
		if($message->notificationType != "Bounce") die("Not a bounce");

		// get the params from the message
		$email = $message->bounce->bouncedRecipients[0]->emailAddress;
		$message = $message->bounce->bouncedRecipients[0]->diagnosticCode;
		$message = str_replace(array("'", "\n"), "", $message);
		$code = explode(" ", $message)[1];

		// save it into the database
		$connection->query("
			INSERT INTO delivery_checked (email, status, code, message)
			VALUES ('$email', 'bounce', '$code', '$message')");
	}
}
