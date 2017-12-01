<?php

use Phalcon\Mvc\Controller;

class AdminController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Dashboard
	 */
	public function indexAction()
	{
		$alerts = Connection::query("SELECT * FROM alerts;");
		var_dump($alerts);
	}
}