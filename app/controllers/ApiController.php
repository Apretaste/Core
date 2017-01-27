<?php

use Phalcon\Mvc\Controller;
use Gregwar\Captcha\CaptchaBuilder;

class ApiController extends Controller
{
	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.0
	 * @param POST email
	 * @param POST pin
	 * @return JSON with token
	 * */
	public function authAction()
	{
		// get the values from the post
		$email = trim($this->request->get('email'));
		$pin = trim($this->request->get('pin'));

		// authenticate and create a new token
		$utils = new Utils();
		$token = $utils->tokenize($email, $pin);
		if( ! $token) die('{"code":"error","message":"invalid email or pin"}');

		// return ok response
		die('{"code":"ok","token":"'.$token.'"}');
	}

	/**
	 * Register a new user from its email
	 *
	 * @author salvipascual
	 * @param GET email
	 * @return JSON with username
	 */
	public function registerAction()
	{
		$email = trim($this->request->get('email'));

		$utils = new Utils();
		$connection = new Connection();

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) die('{"code":"error","message":"invalid email"}');

		// check if the email exist
		if($utils->personExist($email)) die('{"code":"error","message":"existing user"}');

		// create the new profile
		$username = $utils->usernameFromEmail($email);
		$connection->deepQuery("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");

		// return ok response
		die('{"code":"ok","username":"'.$username.'"}');
	}

	/**
	 * Check if an email exist in Apretaste and if the pin is set
	 *
	 * @author salvipascual
	 * @param GET email
	 * @return JSON
	 * */
	public function lookupAction()
	{
		$email = trim($this->request->get('email'));

		// check if the user exist
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email,pin FROM person WHERE LOWER(email)=LOWER('$email')");
		$exist = empty($res) ? 'false' : 'true';

		// check if the user already created a pin
		$pin = "unset";
		if( ! empty($res) && ! empty($res[0]->pin)) $pin = "set";

		die('{"code":"ok","exist":"'.$exist.'","pin":"'.$pin.'"}');
	}

	/**
	 * Creates a new user if it does not exist and email the code
	 *
	 * @author salvipascual
	 * @param GET email
	 * @param GET lang, two digits languge code, IE: en, es
	 * @return JSON
	 * */
	public function startAction()
	{
		// params from GEt and default options
		$email = trim($this->request->get('email'));
		$lang = trim($this->request->get('lang'));
		if(empty($lang)) $lang = "es";

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) die('{"code":"error","message":"invalid email"}');

		$utils = new Utils();
		$connection = new Connection();

		// if user does not exist, create it
		$newUser = "false";
		if( ! $utils->personExist($email))
		{
			$newUser = "true";
			$username = $utils->usernameFromEmail($email);
			$connection->deepQuery("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		$connection->deepQuery("UPDATE person SET pin='$pin' WHERE email='$email'");

		// create response to email the new code
		$subject = "Code: $pin";
		$response = new Response();
		$response->setEmailLayout("email_simple.tpl");
		$response->setResponseSubject($subject);
		$response->createFromTemplate("pinrecover_$lang.tpl", array("pin"=>$pin));
		$response->internal = true;

		// render the template as html
		$render = new Render();
		$body = $render->renderHTML(new Service(), $response);

		// email the code to the user
		$sender = new Email();
		$sender->sendEmail($email, $subject, $body);

		// return ok response
		die('{"code":"ok", "newuser":"'.$newUser.'"}');
	}

}
