<?php

use Phalcon\Mvc\Controller;

class WebhookController extends Controller
{
	public function droppedAction()
	{
		// get the params from post
		$email = $_POST['recipient'];
		$domain = $_POST['domain'];
		$reason = $_POST['reason'];
		$code = $_POST['code'];
		$desc = $_POST['description'];

		// save into the database
		$connection = new Connection();
		$sql = "INSERT INTO delivery_dropped(email,sender,reason,code,description) VALUES ('$email','$domain','$reason','$code','$desc')";
		$connection->deepQuery($sql);
	}
}