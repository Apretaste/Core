<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$email = new Email();
		$email->sendEmail("vsadbsjkd@nauta.cu", "Hola", "Probando");

		echo "Email sent"; 
	}
}