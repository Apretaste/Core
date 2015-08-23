<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$utils = new Utils();
		$raffle = $utils->getCurrentRaffle();
		
		print_r($raffle);
	}
}
