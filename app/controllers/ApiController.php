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
	 */
	public function authAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get the values from the post
		$email = trim($this->request->get('email'));
		$pin = trim($this->request->get('pin'));
		$appid = trim($this->request->get('appid'));
		$appname = trim($this->request->get('appname'));
		$platform = trim($this->request->get('platform')); // android, web, ios

		// check if user/pass is correct
		$connection = new Connection();
		$auth = $connection->query("SELECT email FROM person WHERE LOWER(email)=LOWER('$email') AND pin='$pin'");
		if(empty($auth)) {
			echo '{"code":"error","message":"invalid email or pin"}';
			return false;
		}

		// check if the token exist and grab it
		$token = $connection->query("SELECT token FROM authentication WHERE email='$email' AND appname='$appname'");
		if(isset($token[0]->token)) $token = $token[0]->token;

		// else create a new one and save it to the databae
		if(empty($token))
		{
			$token = md5($email.$pin.rand());
			$connection->query("INSERT INTO authentication (token,email,appid,appname,platform) VALUES ('$token','$email','$appid','$appname','$platform')");
		}

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("AUTH email:$email, pin:$pin, appname:$appname");
		$logger->close();

		// return ok response
		echo '{"code":"ok","token":"'.$token.'"}';
	}

	/**
	 * Update the appid and appname for a certain token
	 *
	 * @author salvipascual
	 * @version 1.0
	 * @param POST token
	 * @param POST appid
	 * @param POST appname
	 * @return JSON with code
	 */
	public function updateAppIdAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get params from GET
		$token = $this->request->get("token");
		$appid = trim($this->request->get('appid'));
		$appname = trim($this->request->get('appname'));

		// force appid and appname
		if(empty($appid) || empty($appname)) {
			echo '{"code":"error","message":"missing appid or appname"}';
			return false;
		}

		// check if token exists
		$connection = new Connection();
		$exist = $connection->query("SELECT COUNT(id) AS exist FROM authentication WHERE token = '$token'")[0]->exist;
		if(empty($exist)) {
			echo '{"code":"error","message":"invalid token"}';
			return false;
		}

		// update appid and appname
		$connection->query("UPDATE authentication SET appid='$appid', appname='$appname' WHERE token='$token'");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("UPDATEAPPID token:$token, appid:$appid, appname:$appname");
		$logger->close();

		// return ok response
		echo '{"code":"ok"}';
	}

	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.1
	 * @param POST email
	 * @param POST pin
	 * @return JSON with token
	 */
	public function logoutAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get the values from the post
		$token = trim($this->request->get('token'));

		// delete the row for the token
		$connection = new Connection();
		$connection->query("DELETE FROM authentication WHERE token='$token'");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("LOGOUT token:$token");
		$logger->close();

		// return ok response
		echo '{"code":"ok"}';
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
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo '{"code":"error","message":"invalid email"}';
			return false;
		}

		// check if the email exist
		if($utils->personExist($email)) {
			echo '{"code":"error","message":"existing user"}';
			return false;
		}

		// create the new profile
		$username = $utils->usernameFromEmail($email);
		$connection->query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("REGISTER email:$email");
		$logger->close();

		// return ok response
		echo '{"code":"ok","username":"'.$username.'"}';
	}

	/**
	 * Check if an email exist in Apretaste and if the pin is set
	 *
	 * @author salvipascual
	 * @param GET email
	 * @return JSON
	 */
	public function lookupAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		$email = trim($this->request->get('email'));

		// check if the user exist
		$connection = new Connection();
		$res = $connection->query("SELECT email,pin FROM person WHERE LOWER(email)=LOWER('$email')");
		$exist = empty($res) ? 'false' : 'true';

		// check if the user already created a pin
		$pin = "unset";
		if( ! empty($res) && ! empty($res[0]->pin)) $pin = "set";

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("LOOKUP user:$email, pin:$pin");
		$logger->close();

		echo '{"code":"ok","exist":"'.$exist.'","pin":"'.$pin.'"}';
	}

	/**
	 * Creates a new user if it does not exist and email the code
	 *
	 * @author salvipascual
	 * @param GET email
	 * @param GET lang, two digits languge code, IE: en, es
	 * @return JSON
	 */
	public function startAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// params from GEt and default options
		$email = trim($this->request->get('email'));
		$lang = trim($this->request->get('lang'));
		if(empty($lang)) $lang = "es";

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo '{"code":"error","message":"invalid email"}';
			return false;
		}

		$utils = new Utils();
		$connection = new Connection();

		// if user does not exist, create it
		$newUser = "false";
		if( ! $utils->personExist($email))
		{
			$newUser = "true";
			$username = $utils->usernameFromEmail($email);
			$connection->query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		$connection->query("UPDATE person SET pin='$pin' WHERE email='$email'");

		// create response to email the new code
		$subject = "Code: $pin";
		$response = new Response();
		$response->email = $email;
		$response->setEmailLayout('email_minimal.tpl');
		$response->setResponseSubject($subject);
		$response->createFromTemplate("pinrecover_$lang.tpl", array("pin"=>$pin));
		$response->internal = true;

		// render the template as html
		$body = Render::renderHTML(new Service(), $response);

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
		echo '{"code":"ok", "newuser":"'.$newUser.'"}';
	}

	/**
	 * Uploades a file via ajax to the temp folder to be process by the web
	 *
	 * @author salvipascual
	 * @param POST file
	 * @return String, URL of the file uploaded
	 */
	public function uploadAction()
	{
		$utils = new Utils();

		// if there is an error upload the file
		if ($_FILES['file']['error'] > 0)
		{
			$msg = 'Error uploading file: ' . $_FILES['file']['error'];
			$utils->createAlert($msg);
			echo '{"code":"error", "message":"'.$msg.'"}';
		}
		// else upload the file and return the path
		else
		{
			$file = $utils->getTempDir() . $_FILES['file']['name'];
			move_uploaded_file($_FILES['file']['tmp_name'], $file);
			echo '{"code":"ok", "message":"'.$file.'"}';
		}
	}

	/**
	 * Save a user's appid to contact him/her later via web push notifications
	 *
	 * @author salvipascual
	 * @param POST email
	 * @param POST appid
	 */
	public function saveAppIdAction()
	{
		$email = $this->request->get('email');
		$appid = $this->request->get('appid');

		// escape values before saving to the db
		$email = Connection::escape($email);
		$appid = Connection::escape($appid);

		// create login token
		$utils = new Utils();
		$token = $utils->generateRandomHash();

		// check if the row already exists
		$row = Connection::query("SELECT appid FROM authentication WHERE email='$email' AND appname='apretaste' AND platform='web'");

		// if the row do not exist, create it
		if(empty($row)) {
			Connection::query("INSERT INTO authentication(token,email,appid,appname,platform) VALUES ('$token','$email','$appid','apretaste','web')");
		}

		// if the row exist and the appid is different, update it
		elseif($row[0]->appid != $appid) {
			Connection::query("UPDATE authentication SET appid='$appid', token='$token' WHERE email='$email' AND appname='apretaste' AND platform='web'");
		}
	}
}
