<?php

use Phalcon\Mvc\Controller;

class LoginController extends Controller
{
	/**
	 * Start the user session
	 */
	public function indexAction()
	{
		$this->view->redirect = $this->request->get('redirect');
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
	public function asyncSendCodeAction()
	{
		// params from GEt and default options
		$email = trim($this->request->get('email'));

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) die('{"code":"error"}');

		// create the user if he/she does not exist
		$connection = new Connection();
		$utils = new Utils();
		if( ! $utils->personExist($email)) {
			$username = $utils->usernameFromEmail($email);
			$connection->query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'web')");
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
		$render = new Render();
		$body = $render->renderHTML(new Service(), $response);

		// email the code to the user
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = "Code: $pin";
		$sender->body = $body;
		$res = $sender->send();

		// return response
		if($res->code == "200") die('{"code":"ok"}');
		else die('{"code":"error"}');
	}

	/**
	 * Start the user session
	 */
	public function asyncLoginSubmitAction()
	{
		// get data from post
		$email = $this->request->get('email');
		$pin = $this->request->get('pin');
		$redirect = $this->request->get('redirect');

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || empty($pin)) die('{"code":"error"}');

		// try to log the user and redirect to default page
		$security = new Security();
		$user = $security->login($email, $pin);

		// get redirect link to send after login
		if($user) {
			if($user->isManager) $redirectLink = $user->startPage;
			else {
				if(empty($redirect)) $redirect = "servicios";
				$redirectLink = "/run/display?subject=$redirect";
			}
			die('{"code":"ok", "redirect":"'.$redirectLink.'"}');
		}
		else die('{"code":"error"}');
	}
}
