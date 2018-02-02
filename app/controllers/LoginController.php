<?php

use Phalcon\Mvc\Controller;

class LoginController extends Controller
{
	/**
	 * Ask for email to login
	 */
	public function indexAction()
	{
		$this->view->phase = "email";
		$this->view->action = "";
		$this->view->email = $this->request->get('email');
		$this->view->redirect = $this->request->get('redirect');
		$this->view->icon = $this->getIcon($this->request->get('redirect'));
		$this->view->shake = $this->request->get('shake');
		$this->view->setLayout('login');
	}

	/**
	 * Ask for the code to login
	 */
	public function codeAction()
	{
		$this->view->phase = "code";
		$this->view->action = $this->request->get('action');
		$this->view->email = $this->request->get('email');
		$this->view->redirect = $this->request->get('redirect');
		$this->view->icon = $this->getIcon($this->request->get('redirect'));
		$this->view->shake = $this->request->get('shake');
		$this->view->setLayout('login');
	}

	/**
	 * Close the session for a user
	 */
	public function logoutAction()
	{
		$security = new Security();
		$security->logout();
	}

	/**
	 * Email the code to the user
	 *
	 * @param GET email
	 * @return JSON
	 */
	public function emailSubmitAction()
	{
		// params from GET and default options
		$email = $this->request->get('email');
		$redirect = $this->request->get('redirect');

		// check if the email is valid
		if( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return false;
		}

		// try to login by IP to avoid sending code
		$security = new Security();
		$user = $security->loginByIP($email);
		if($user) {
			// get redirect link and redirect
			$redirect = empty($redirect) ? "servicios" : $redirect;
			if($user->isManager) $this->response->redirect($user->startPage);
			else $this->response->redirect("run/display?subject=$redirect&action=login");
			$this->view->disable();
			return false;
		}

		// create the user if he/she does not exist
		$utils = new Utils();
		$connection = new Connection();
		$action = "login";
		if( ! $utils->personExist($email)) {
			$username = $utils->usernameFromEmail($email);
			$connection->query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'web')");
			$action = "register";
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		$connection->query("UPDATE person SET pin='$pin' WHERE email='$email'");

		// create response to email the new code
		$response = new Response();
		$response->email = $email;
		$response->setEmailLayout('email_minimal.tpl');
		$response->createFromTemplate("pinrecover_es.tpl", array("pin"=>$pin));
		$response->internal = true;

		// render the template as html
		$body = Render::renderHTML(new Service(), $response);

		// email the code to the user
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = "Code: $pin";
		$sender->body = $body;
		$res = $sender->send();

		// redirect to ask for code
		$email = urlencode($email);
		$this->response->redirect("login/code?email=$email&redirect=$redirect&action=$action");
		$this->view->disable();
	}

	/**
	 * Submit the code and start the user session
	 */
	public function codeSubmitAction()
	{
		// get data from post
		$email = $this->request->get('email');
		$pin = $this->request->get('pin');
		$action = $this->request->get('action');
		$redirect = $this->request->get('redirect');

		// check if the email is valid
		if( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return false;
		}

		// log in the user
		$security = new Security();
		$user = $security->login($email, $pin);

		// error if the user cannot be logged
		if(empty($user)) {
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return false;
		}

		// get redirect link and redirect
		$redirect = empty($redirect) ? "servicios" : $redirect;
		if($user->isManager) $redirectLink = $user->startPage;
		else $redirectLink = "run/display?subject=$redirect&action=$action";

		// redirect to page
		$this->response->redirect($redirectLink);
		$this->view->disable();
	}

	/**
	 * Get the icon for the service
	 */
	public function getIcon($redirect)
	{
		// create the default icon path
		$icon = "/images/apretaste.logo.small.transp.png";

		// get the path to the icon of the service
		$wwwroot = $this->di->get('path')['root'];
		$service = strstr($redirect.' ', ' ', true);
		$pathToIcon = "$wwwroot/services/$service/$service.png";

		// change the icon if exist
		if(file_exists($pathToIcon)) {
			$publicPathToIcon = "$wwwroot/public/temp/$service.png";
			if( ! file_exists($publicPathToIcon)) copy($pathToIcon, $publicPathToIcon);
			$icon = $this->di->get('path')['http'] . "/temp/$service.png";
		}

		return $icon;
	}
}
