<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$email = new Email();
		$images = array("/home/salvipascual/Pictures/pascuals.jpg", "/home/salvipascual/Pictures/pascuals.png");
		$body = '<html>Inline image:<img alt="image1" src="cid:pascuals.jpg"><br/><img alt="image2" src="cid:pascuals.png"></html>';

		echo $email->deliveryStatus("salvi@nfomed.sld.cu");

		exit;
		
		$email->sendEmail("apretaste@recipescookbook.org", "Test", $body);

		echo "Email sent"; 
	}
}