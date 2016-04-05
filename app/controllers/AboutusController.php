<?php

use Phalcon\Mvc\Controller;

class AboutusController extends Controller
{
	public function indexAction()
	{
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->stripePushibleKey = $this->di->get('config')['stripe']['pushible'];
		$this->view->pick("index/aboutus");

	}
}
