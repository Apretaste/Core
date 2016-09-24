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
		die('{"code":"ok","token","'.$token.'"}');
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
	 * Check if an email exist in Apretaste's database
	 * Else check if the pin is set to create it in case is not
	 * 
	 * @author salvipascual
	 * @param GET email
	 * @return JSON
	 * */
	public function personExistAction()
	{
		$email = trim($this->request->get('email'));

		$connection = new Connection();

		// check if the user exist
		$res = $connection->deepQuery("SELECT email,pin FROM person WHERE LOWER(email)=LOWER('$email')");
		$exist = empty($res) ? 'false' : 'true';

		// check if the user already created a pin
		$pin = "unset";
		if( ! empty($res) && ! empty($res[0]->pin)) $pin = "set"; 

		die('{"exist":"'.$exist.'", "pin":"'.$pin.'"}');
	}

	/**
	 * Load a person's information based on username (more secure than email)
	 * 
	 * @author salvipascual
	 * @param GET username
	 * @return JSON
	 * */
	public function getPersonAction()
	{
		$username = trim($this->request->get('user'));

		$utils = new Utils();
		$connection = new Connection();

		// get email from the username
		$email = $connection->deepQuery("SELECT email FROM person WHERE username='$username'");
		if(empty($email)) die('{"code":"error","message":"invalid username"}');
		$email = $email[0]->email;

		// get person 
		$person = $utils->getPerson($email);
		die(json_encode($person));
	}

	/**
	 * Recovers a pin and create a pin for users with blank pins
	 *
	 * @author salvipascual
	 * @param GET email
	 * @return JSON
	 * */
	public function recoverAction()
	{
		$email = trim($this->request->get('email'));

		$utils = new Utils();
		$connection = new Connection();

		// check if the email exist
		if( ! $utils->personExist($email)) die('{"code":"error","message":"invalid user"}');

		// get pin from the user
		$pin = $connection->deepQuery("SELECT pin FROM person WHERE email='$email'");
		$pin = $pin[0]->pin;

		// if pin is blank, create it
		if(empty($pin))
		{
			$pin = mt_rand(1000, 9999);
			$connection->deepQuery("UPDATE person SET pin='$pin' WHERE email='$email'");
		}

		// create response to email the new code
		$subject = "Su codigo de Apretaste";
		$response = new Response();
		$response->setEmailLayout("email_simple.tpl");
		$response->setResponseSubject($subject);
		$response->createFromTemplate("pinrecover.tpl", array("pin"=>$pin));
		$response->internal = true;

		// render the template as html
		$render = new Render();
		$body = $render->renderHTML(new Service(), $response);

		// email the code to the user
		$emailSender = new Email();
		$emailSender->sendEmail($email, $subject, $body);

		// return ok response
		die('{"code":"ok"}');
	}
}