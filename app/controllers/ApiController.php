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
		$appname = trim($this->request->get('appname')); // apretaste, pizarra, piropazo
		$platform = trim($this->request->get('platform')); // android, web, ios

		// check if user/pass is correct
		$auth = Connection::query("SELECT email,token FROM person WHERE LOWER(email)=LOWER('$email') AND pin='$pin'");
		if(empty($auth)) {
			echo '{"code":"error","message":"invalid email or pin"}';
			return false;
		}

		// save token in the database if it does not exist
		$token = trim($auth[0]->token);
		if(empty($token)) {
			$token = md5($email.$pin.rand());
			Connection::query("UPDATE person SET token='$token' WHERE email='$email'");
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

		// delete the token
		Connection::query("UPDATE person SET token=NULL WHERE token='$token'");

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

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo '{"code":"error","message":"invalid email"}';
			return false;
		}

		// check if the email exist
		if(Utils::personExist($email)) {
			echo '{"code":"error","message":"existing user"}';
			return false;
		}

		// create the new profile
		$username = Utils::usernameFromEmail($email);
		Connection::query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");

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
		$res = Connection::query("SELECT email,pin FROM person WHERE LOWER(email)=LOWER('$email')");
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
		$lang = strtolower(trim($this->request->get('lang')));
		if(array_search($lang, ['es', 'en']) === false) $lang = "es";

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo '{"code":"error","message":"invalid email"}';
			return false;
		}

		// if user does not exist, create it
		$newUser = "false";
		if( ! Utils::personExist($email))
		{
			$newUser = "true";
			$username = Utils::usernameFromEmail($email);
			Connection::query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		Connection::query("UPDATE person SET pin='$pin' WHERE email='$email'");

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
		$res = $sender->send();

		// return error response
		if($res->code != "200") {
			echo '{"code":"error", "message":"'.$res->message.'"}';
			return false;
		}

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
		// if there is an error upload the file
		if ($_FILES['file']['error'] > 0)
		{
			$msg = 'Error uploading file: ' . $_FILES['file']['error'];
			Utils::createAlert($msg);
			echo json_encode(["code"=>"error", "message"=>$msg]);
		}
		// else upload the file and return the path
		else
		{
			$file = Utils::getTempDir() . "attach_images/" . $_FILES['file']['name'];
			move_uploaded_file($_FILES['file']['tmp_name'], $file);
			echo json_encode(["code"=>"ok", "message"=>$file]);
		}
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
		$email = Utils::detokenize($token);
		if(empty($email)) {
			echo '{"code":"error","message":"invalid token"}';
			return false;
		}

		// get the person's numeric ID
		$personId = Utils::personExist($email);

		// update appid and appname
		Connection::query("
			DELETE FROM authentication WHERE appname='$appname' AND person_id='$personId';
			INSERT INTO authentication (person_id,appid,appname) VALUES ('$personId','$appid','$appname')");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("UPDATEAPPID token:$token, appid:$appid, appname:$appname");
		$logger->close();

		// return ok response
		echo '{"code":"ok"}';
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
		$token = Utils::generateRandomHash();

		// get the person's numeric ID
		$personId = Utils::personExist($email);

		// check if the row already exists
		$row = Connection::query("SELECT appid FROM authentication WHERE person_id='$personId' AND appname='apretaste' AND platform='web'");
		
		// if the row do not exist, create it
		if(empty($row)) {
			Connection::query("INSERT INTO authentication(token,person_id,appid,appname,platform) VALUES ('$token','$personId','$appid','apretaste','web')");
		}
		// if the row exist and the appid is different, update it
		elseif($row[0]->appid != $appid) {
			Connection::query("UPDATE authentication SET appid='$appid', token='$token' WHERE person_id='$personId' AND appname='apretaste' AND platform='web'");
		}
	}

	/**
	 * Used for the app to check if there is http conectivity
	 *
	 * @author salvipascual
	 * @version 1.0
	 */
	public function checkAction()
	{
		echo '{"code":"200"}';
	}

  /**
   * Check if user exists
   *
   * @param $userId
   * @throws Phalcon\Exception
   */
  public function checkUserAction($userId)
  {
    if ( ! Utils::isInternalNetwork())
    {
      throw new Phalcon\Exception("Access denied for " . php::getClientIP() . " to Api::checkUserAction");
    }

    $this->response->setHeader("Content-type", "application/json");

    echo (Utils::getEmailFromId($userId) === false) ?
      '{"result": false }' : '{"result": true}';
  }

  /**
   * Check token
   *
   * @param $token
   * @param $userId
   *
   * @throws Phalcon\Exception
   */
  public function checkTokenAction($userId, $token){

    if ( ! Utils::isInternalNetwork())
    {
      throw new Phalcon\Exception("Access denied for " . php::getClientIP() . " to Api::checkUserAction");
    }

    $this->response->setHeader("Content-type", "application/json");

    $security = new Security();
    $user = $security->loginByToken($token);
    echo ($user === false) ?
      '{"result": false }' : ($user->id === $userId ? '{"result": true}' : '{"result": false}');

  }

  /**
   * Apretin Bot
   *
   * @author kumahacker
   * @throws Phalcon\Exception
   */
  public function apretinAction($retoken = ''){

    // logger function
    $log = function($message) {
      $wwwroot = $this->di->get('path')['root'];
      $logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/apretin.log");
      if ( ! is_array($message)) $message = [$message];
      foreach($message as $msg) $logger->log($msg);
      $logger->close();
    };

    // get api token and message
    $token = $this->di->get('config')['telegram']['apretin_token'];
    $security = $this->di->get('config')['telegram']['retoken'];
    $message = $this->request->getJsonRawBody(true);

    if ($retoken != $security)
    {
        throw new Phalcon\Exception("Apretin access denied");
    }

    $log([
      "Getting message ",
      json_encode($message)
    ]);

    $sendMessage = function($chat_id, $message, $tk)
    {
      $wwwroot = $this->di->get('path')['root'];
      $results = Utils::file_get_contents_curl("https://api.telegram.org/bot{$tk}/sendMessage?chat_id=$chat_id&text=".urlencode($message)."&parse_mode=HTML");
      $logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/apretin.log");
      $logger->log(date("Y-m-d h:i:s\n"));
      $logger->log("Sending message to telegram @$chat_id: ".substr($message, 0,40));
      $logger->log("  RESULT: ".json_encode($results));
      $logger->log("\n\n");
      $logger->close();
    };

    if (isset($message['message'])) {
      $username = $message['message']['from']['username'];
      $chat     = $message['message']['chat']['username'];
      $chat_id  = $message['message']['chat']['id'];
      $text     = $message['message']['text'];

      $log([
        date("Y-m-d h:i:s\n"),
        "Get message " . substr($text, 0, 40) . "from @$username in @$chat",
        "\n\n"
      ]);

      if (isset($message['message']['new_chat_members'])) {
        foreach ($message['message']['new_chat_members'] as $newMember) {
          $sendMessage($chat_id, "Hola {$newMember['first_name']} {$newMember['last_name']}, te doy la bienvenida a Apretaste. Comparte con esta gran familia.", $token);
        }
      }

      if (isset($message['message']['left_chat_member'])) {
        $leftMember = $message['message']['left_chat_member'];
        $sendMessage($chat_id, "Es triste que te vayas {$leftMember['first_name']} {$leftMember['last_name']}. Esperemos que regreses pronto a compartir con la gran familia de Apretaste.", $token);
      }

      if ($text[0] == '/') {

        $text = substr($text, 1);

        if (stripos($text, 'audiencia') === 0) {
          $r = Connection::query("SELECT count(*) as total FROM delivery where datediff(current_date, date(request_date)) <= 7;");
          $r1 = Connection::query("SELECT count(*) AS total FROM person WHERE datediff(current_date, date(last_access)) <= 7;");

          $sendMessage($chat_id, "En los &uacute;ltimos 7 d&iacute;s, <strong>{$r1[0]->total} usuarios</strong> han usado nuestra #app unas <strong>{$r[0]->total} veces</strong>.", $token);
          return;
        }

        if (stripos($text, 'enlaces') === 0) {

          $sendMessage($chat_id, "Estamos en las redes sociales:\n\n" .
                                 "Facebook - https://www.facebook.com/apretaste/\n" .
                                 "Twitter - https://twitter.com/apretaste\n" .
                                 "Youtube - https://www.youtube.com/c/Apretaste\n", $token);
          return;
        }

        $sendMessage($chat_id, "Lo siento @$username, pero no entendi que quisiste decir.", $token);
      }

      $sendMessage($chat_id, ":D", $token);
    }
  }
}
