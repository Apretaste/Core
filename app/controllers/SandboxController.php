<?php

use Phalcon\Mvc\Controller;

class SandboxController extends Controller
{
	public function indexAction()
	{
		$this->view->setLayout('manage');
		$this->view->title = "Apretaste Sandbox";
	}
}
