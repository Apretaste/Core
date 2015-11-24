<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$to = "geovannyashley@nauta.cu";

		// do not get deep check for existing emails
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email FROM person  WHERE email='$to'");

		if(empty($res))
		{
			// build and send the API request
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$key = $di->get('config')['emailvalidator']['key'];
			$result = json_decode(@file_get_contents("https://api.email-validator.net/api/verify?EmailAddress=$to&APIKey=$key"));
			if($result && $result->status > 300 && $result->status < 399) echo "soft-bounce";
			if($result && $result->status > 400 && $result->status < 499) echo "hard-bounce";

			print_r($result);
		}

		exit;

		$email = new Email();
		$images = array("/home/salvipascual/Pictures/pascuals.jpg", "/home/salvipascual/Pictures/pascuals.png");
		$body = '<html>Inline image:<img alt="image1" src="cid:pascuals.jpg"><br/><img alt="image2" src="cid:pascuals.png"></html>';

		echo $email->deliveryStatus("salvi@nfomed.sld.cu");

		exit;
		
		$email->sendEmail("apretaste@recipescookbook.org", "Test", $body);

		echo "Email sent"; 
	}
}