<?php

use Phalcon\Mvc\Controller;

class LoginController extends Controller
{
	/**
	 * Start the user session
	 */
	public function indexAction()
	{
		// check if the session is open
		$security = new Security();
		$isUserLogged = $security->checkLogin();

		// if logged, redirect to the default page
		if($isUserLogged) {
			$manager = $security->getManager();
			header("Location: {$manager->startPage}");
		}

		$this->view->setLayout('login');
	}

	/**
	 * Start the user session
	 */
	public function loginSubmitAction()
	{
		// get data from post
		$email = $this->request->getPost('email');
		$password = $this->request->getPost('password');

		// try to log the user and redirect to default page
		$security = new Security();
		$security->login($email, $password);

		// refresh page if not logged
		$this->response->redirect("login");
	}

	/**
	 * Close the session for a user
	 */
	public function logoutAction()
	{
		$security = new Security();
		$security->logout();
	}
}
