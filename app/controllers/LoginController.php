<?php

use Phalcon\Mvc\Controller;
use \Phalcon\Logger\Adapter\File;

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
	 */
	public function emailSubmitAction()
	{
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/web.log");

		// params from GET and default options
		$email = strtolower($this->request->get('email'));
		$redirect = $this->request->get('redirect');
		$redirect = empty($redirect) ? "servicios" : $redirect;
		$action = "login";

		$logger->log("Login| User {$email} start the login");

		// check if the email is valid
		if( ! filter_var($email, FILTER_VALIDATE_EMAIL)){
			$logger->log("Login| {$email} is not valid email address, redirecting");
			$logger->close();

			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return false;
		}

		if( ! Utils::isAllowedDomain($email)){
			$logger->log("Login| Domain of user {$email} is not allowed");
			$logger->close();
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return;
		}

		$logger->log("Login| Checking if user {$email} exists");
		$person = Utils::getPerson($email);

		if($person){
			if($person->blocked){
				$logger->log("Login| User blocked: {$email}");
				$logger->close();

				$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
				$this->view->disable();
				return;
			}

			//try to login by IP to avoid sending code
			$user = Security::loginByIP($person);
			if($user){
				$logger->log("Login| User {$email} start session with IP login");
				$logger->close();

				// get redirect link and redirect
				if($user->isManager) $this->response->redirect($user->startPage);
				else $this->response->redirect("run/web?cm=$redirect&action=login");
				$this->view->disable();
				return;
			}
		}
		else{
			// create the user if he/she does not exist
			$logger->log("Login| User {$email} is new user!");

			$username = Utils::usernameFromEmail($email);
			Connection::query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'web')");
			$action = "register";
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		Connection::query("UPDATE person SET pin='$pin' WHERE email='$email'");

		$body = "Su codigo secreto es: $pin.
		Use este codigo para registrarse en nuestra app o web. 
		Si usted no esperaba este codigo, elimine este email ahora. 
		Por favor no comparta el numero con nadie que se lo pida.";

		$logger->log("Login| Sending PIN code to {$email}");

		// email the code to the user
		$codeEmail = new Email();
		$codeEmail->to = $email;
		$codeEmail->subject = "Code: $pin";
		$codeEmail->body = $body;
		$codeEmail->queue();

		$logger->log("Login| PIN code $pin sent successful to {$email}");
		$logger->close();

		// redirect to ask for code
		$this->response->redirect("login/code?email=$email&redirect=$redirect&action=$action");
		$this->view->disable();
	}

	/**
	 * Submit the code and start the user session
	 */
	public function codeSubmitAction()
	{
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/web.log");

		// get data from post
		$email = $this->request->get('email');
		$pin = $this->request->get('pin');
		$action = $this->request->get('action');
		$redirect = $this->request->get('redirect');
		$redirect = empty($redirect) ? "servicios" : $redirect;

		$logger->log("Login | Receive PIN code $pin from {$email}");

		// check if the email is valid
		if( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return;
		}

		if( ! Utils::isAllowedDomain($email)){
			$logger->log("Login| Domain of user {$email} is not allowed");
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return;
		}

		$person = Utils::getPerson($email);
		if($person){
			if($person->blocked){
				$logger->log("Login| User {$email} with code: {$pin} blocked");
				$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
				$this->view->disable();
				return;
			}

			// log in the user
			$user = Security::login($person);
		}
		else {
			$logger->log("Login | User {$email} with code: {$pin} doesn't exists");
			$this->response->redirect("login?email=$email&redirect=$redirect&shake=true");
			$this->view->disable();
			return;
		}

		// redirect
		if($user->isManager) $redirectLink = $user->startPage;
		else $redirectLink = "run/web?cm=$redirect&action=$action";

		$logger->log("Login | Login successful, redirect to $redirectLink");
		$logger->close();

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
		$icon = "/images/apretaste_logo_250x90.png";

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
