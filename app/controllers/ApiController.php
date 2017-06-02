<?php

use Phalcon\Mvc\Controller;

class ApiController extends Controller
{
	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.1
	 * @param POST email
	 * @param POST pin
	 * @return JSON with token
	 * */
	public function authAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get the values from the post
		$email = trim($this->request->get('email'));
		$pin = trim($this->request->get('pin'));
		$appid = trim($this->request->get('appid'));
		$appname = trim($this->request->get('appname'));

		// check if user/pass is correct
		$connection = new Connection();
		$auth = $connection->deepQuery("SELECT email FROM person WHERE LOWER(email)=LOWER('$email') AND pin='$pin'");
		if(empty($auth)) die('{"code":"error","message":"invalid email or pin"}');

		// get the new expiration date and token
		$expires = date("Y-m-d", strtotime("+1 month"));
		$token = md5($email.$pin.$expires.rand());

		// create new entry on the authentication table
		// and delete all previous entries for this token
		$connection->deepQuery("
			START TRANSACTION;
			DELETE FROM authentication WHERE email='$email' AND appname = '$appname';
			INSERT INTO authentication (token,email,appid,appname,expires) VALUES ('$token','$email','$appid','$appname','$expires');
			COMMIT");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("AUTH email:$email, pin:$pin, appname:$appname");
		$logger->close();

		// return ok response
		die('{"code":"ok","token":"'.$token.'"}');
	}

	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.1
	 * @param POST email
	 * @param POST pin
	 * @return JSON with token
	 * */
	public function logoutAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get the values from the post
		$token = trim($this->request->get('token'));

		// delete the row for the token
		$connection = new Connection();
		$connection->deepQuery("DELETE FROM authentication WHERE token='$token'");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("LOGOUT token:$token");
		$logger->close();

		// return ok response
		die('{"code":"ok"}');
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
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

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

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("REGISTER email:$email");
		$logger->close();

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
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		$email = trim($this->request->get('email'));

		// check if the user exist
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email,pin FROM person WHERE LOWER(email)=LOWER('$email')");
		$exist = empty($res) ? 'false' : 'true';

		// check if the user already created a pin
		$pin = "unset";
		if( ! empty($res) && ! empty($res[0]->pin)) $pin = "set";

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("LOOKUP user:$email, pin:$pin");
		$logger->close();

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
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

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
		$response->email = $email;
		$response->setEmailLayout('email_minimal.tpl');
		$response->setResponseSubject($subject);
		$response->createFromTemplate("pinrecover_$lang.tpl", array("pin"=>$pin));
		$response->internal = true;

		// render the template as html
		$render = new Render();
		$body = $render->renderHTML(new Service(), $response);

		// email the code to the user
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = $subject;
		$sender->body = $body;
		$sender->send();

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("START email:$email, lang:$lang, new:$newUser");
		$logger->close();

		// return ok response
		die('{"code":"ok", "newuser":"'.$newUser.'"}');
	}

	/**
	 * Sends an email using the anti-censorship engine
	 *
	 * @author salvipascual
	 * @param String64 $key: Base64 of "$email:$pass"
	 * @param String $email
	 * @param String $subject
	 * @param String $body
	 */
	public function sendEmailAction()
	{
		// get params from GET (or from the encripted API)
		$key = $this->request->get("key");
		$email = $this->request->get("email");
		$subject = $this->request->get("subject");
		$body = file_get_contents("php://input");

		// do not allow empty emails
		if(empty($email) || empty($body))
		{
			die('{"status":"0", "message":"Empty email"}');
		}

		// create a random subject if empty
		if(empty($subject))
		{
			$utils = new Utils();
			$subject = $utils->randomSentence();
		}

		// check user pass combination
		$loginInfo = explode(":", base64_decode($key));
		$security = new Security();
		$res =  $security->checkUserPass($loginInfo[0], $loginInfo[1]);

		// check is the user has permissions to forward
		$pages = explode(",", $res->items->pages);
		if( ! (in_array("*", $pages) || in_array("forward", $pages)))
		{
			die('{"status":"0", "message":"No permissions"}');
		}

		// forward the email
		$sender = new Email();
		$sender->setGroup($res->items->group);
		$sender->sendEmail($email, $subject, $body);

		// display OK message
		die('{"status":"1", "message":""}');
	}
}
