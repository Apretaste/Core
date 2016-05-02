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

		// save into the database
		$connection = new Connection();
		$sql = "INSERT INTO delivery_dropped(email,sender,reason,code,description) VALUES ('$email','$domain','$reason','$code','$desc')";
		$connection->deepQuery($sql);

		// echo completion message
		echo "FINISHED";
	}
}