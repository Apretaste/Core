<?php

use Phalcon\Mvc\Controller;
use \Phalcon\Logger\Adapter\File;

class ApiController extends Controller
{
	/**
	 * Check if user is blocked
	 *
	 * @param $email
	 * @return bool
	 */
	public function isEnabledUser($email)
	{
		// check if the user is blocked
		if (Utils::isUserBlocked($email)) {
			$wwwroot = $this->di->get('path')['root'];
			$logger	= new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
			$logger->log("User:$email, BLOCKED!");
			$logger->close();
			echo '{"code":"error","message":"user blocked"}';
			return false;
		}

		if ( ! Utils::isAllowedDomain($email)) {
			$wwwroot = $this->di->get('path')['root'];
			$logger	= new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
			$logger->log("Domain of email:$email not allowed");
			$logger->close();

			echo '{"code":"error","message":"domain not allowed"}';
			return false;
		}

		return true;
	}

	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.1
	 *
	 * @param POST email
	 * @param POST pin
	 *
	 * @return bool | JSON with token
	 */
	public function authAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		//Open the logger
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/api.log");

		// get the values from the post
		$email = strtolower(trim($this->request->get('email')));

		if ( ! $this->isEnabledUser($email)) return true;

		$pin = trim($this->request->get('pin'));
		$appid = trim($this->request->get('appid'));
		$appname = trim($this->request->get('appname')); // apretaste, pizarra, piropazo
		$platform = trim($this->request->get('platform')); // android, web, ios

		if( ! Utils::isAllowedDomain($email)) {
			$logger->log("AUTH domain of email:$email from appname:$appname not allowed");
			$logger->close();

			echo '{"code":"error","message":"domain not allowed"}';
			return;
		}

		// check if user/pass is correct
		$auth = Connection::query("SELECT email,token, blocked FROM person WHERE email='$email' AND pin='$pin'");
		if(empty($auth)){
			$logger->log("AUTH email($email) or pin($pin) invalid");
			$logger->close();

			echo '{"code":"error","message":"invalid email or pin"}';
			return;
		}

		if($auth[0]->blocked){
			$logger->log("AUTH user $email is blocked");
			$logger->close();

			echo '{"code":"error","message":"user is blocked"}';
			return;
		}

		// save token in the database if it does not exist
		$token = trim($auth[0]->token);
		if(empty($token)){
			$token = md5($email.$pin.rand());
			Connection::query("UPDATE person SET token='$token' WHERE email='$email'");
		}

		// save the API log
		$logger->log("AUTH email:$email, pin:$pin, appname:$appname");
		$logger->close();

		// return ok response
		echo '{"code":"ok","token":"' . $token . '"}';

		return true;
	}

	/**
	 * Authenticate an user and return the token
	 *
	 * @author salvipascual
	 * @version 1.1
	 *
	 * @param POST email
	 * @param POST pin
	 *
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
		$logger = new File("$wwwroot/logs/api.log");
		$logger->log("LOGOUT token:$token");
		$logger->close();

		// return ok response
		echo '{"code":"ok"}';
	}

	/**
	 * Register a new user from its email
	 *
	 * @author salvipascual
	 *
	 * @param GET email
	 *
	 * @return bool | JSON with username
	 */
	public function registerAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/api.log");
		$email = strtolower(trim($this->request->get('email')));

		if ( ! $this->isEnabledUser($email)) return false;

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$logger->log("AUTH email:$email invalid");
			$logger->close();

			echo '{"code":"error","message":"invalid email"}';
			return;
		}

		if( ! Utils::isAllowedDomain($email)){
			$logger->log("AUTH domain of email:$email not allowed");
			$logger->close();

			echo '{"code":"error","message":"domain not allowed"}';
			return;
		}

		// check if the email exist
		if(Utils::getPerson($email)) {
			echo '{"code":"error","message":"existing user"}';
			return;
		}

		// create the new profile
		$username = Utils::usernameFromEmail($email);
		Connection::query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");

		// save the API log
		$logger->log("REGISTER email:$email");
		$logger->close();

		// return ok response
		echo '{"code":"ok","username":"' . $username . '"}';
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

		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/api.log");

		$email = strtolower(trim($this->request->get('email')));

		if( ! Utils::isAllowedDomain($email)){
			$logger->log("LOOKUP domain of email:$email not allowed");
			$logger->close();

			echo '{"code":"error","message":"domain not allowed"}';
			return;
		}

		// check if the user exist
		$res = Connection::query("SELECT email, pin, blocked FROM person WHERE email='$email'");
		$exist = empty($res) ? 'false' : 'true';

		if( ! empty($res) && $res[0]->blocked){
			$logger->log("LOOKUP user $email is blocked");
			$logger->close();

			echo '{"code":"error","message":"user is blocked"}';
			return;
		}

		// check if the user already created a pin
		$pin = "unset";
		if( ! empty($res) && ! empty($res[0]->pin)) $pin = "set";

		// save the API log
		$logger->log("LOOKUP user:$email, pin:$pin");
		$logger->close();

		echo '{"code":"ok","exist":"' . $exist . '","pin":"' . $pin . '"}';
	}

	/**
	 * Send PIN to user
	 *
	 * @param $email
	 * @param $pin
	 * @param object $res
	 *
	 * @return bool
	 */
	private function sendPIN($email, $pin, &$res = null)
	{
		$wwwroot = $this->di->get('path')['root'];

		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("SENT PIN email:$email, pin:$pin");
		$logger->close();

		Connection::query("UPDATE person SET pin='$pin' WHERE email='$email'");

		$subject = "Code: $pin";
		$body = "Su codigo secreto es: $pin. Use este codigo para registrarse en nuestra app o web. Si usted no esperaba este codigo, elimine este email ahora. Por favor no comparta el numero con nadie que se lo pida.";

		// email the code to the user
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = $subject;
		$sender->body = $body;
		$res = $sender->queue();

		return $res->code === "200";
	}

	/**
	 * Creates a new user if it does not exist and email the code
	 *
	 * @author salvipascual
	 *
	 * @param GET email
	 * @param GET lang, two digits languge code, IE: en, es
	 *
	 * @return string | boolean
	 */
	public function startAction()
	{
		$wwwroot = $this->di->get('path')['root'];

		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/api.log");// params from GEt and default options
		$email = trim($this->request->get('email'));

		if (!$this->isEnabledUser($email)) return false;

		$lang = strtolower(trim($this->request->get('lang')));
		if(array_search($lang, ['es', 'en']) === FALSE) { $lang = "es";}

		// check if the email is valid
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$logger->log("AUTH email:$email invalid");
			$logger->close();

			echo '{"code":"error","message":"invalid email"}';
			return false;
		}

		if( ! Utils::isAllowedDomain($email)) {
			$logger->log("LOOKUP domain of email:$email not allowed");
			$logger->close();

			echo '{"code":"error","message":"domain not allowed"}';
			return;
		}

		// if user does not exist, create it
		$newUser = "false";
		$person = Utils::getPerson($email);
		if($person && $person->blocked) {
			$logger->log("LOOKUP user $email is blocked");
			$logger->close();

			echo '{"code":"error","message":"user is blocked"}';
			return;
		} elseif( ! $person){
			$newUser = "true";
			$username = Utils::usernameFromEmail($email);
			Connection::query("INSERT INTO person (email, username, source) VALUES ('$email', '$username', 'api')");
		}

		// create a new pin for the user
		$pin = mt_rand(1000, 9999);
		$res = null;
		$this->sendPIN($email, $pin, $res);

		// return error response
		if($res->code != "200") {
			echo '{"code":"error", "message":"'.$res->message.'"}';
			return false;
		}

		// save the API log
		$logger->log("START email:$email, lang:$lang, new:$newUser");
		$logger->close();

		// return ok response
		echo '{"code":"ok", "newuser":"'.$newUser.'"}';
		return true;
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
		if ($_FILES['file']['error'] > 0){
			$msg = 'Error uploading file: ' . $_FILES['file']['error'];
			Utils::createAlert($msg);
			echo json_encode(["code"=>"error", "message"=>$msg]);
		}
		// else upload the file and return the path
		else{
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
	 *
	 * @param POST token
	 * @param POST appid
	 * @param POST appname
	 *
	 * @return JSON with code
	 */
	public function updateAppIdAction()
	{
		// allow JS clients to use the API
		header("Access-Control-Allow-Origin: *");

		// get params from GET
		$token	 = $this->request->get("token");
		$appid	 = trim($this->request->get('appid'));
		$appname = trim($this->request->get('appname'));

		// force appid and appname
		if (empty($appid) || empty($appname)) {
			echo '{"code":"error","message":"missing appid or appname"}';
			return FALSE;
		}

		// check if token exists
		$email = Utils::detokenize($token);
		if (empty($email)) {
			echo '{"code":"error","message":"invalid token"}';
			return FALSE;
		}

		// get the person's numeric ID
		$person = Utils::gerPerson($email);

		// update appid and appname
		Connection::query("
			DELETE FROM authentication WHERE appname='$appname' AND person_id='{$person->id}';
			INSERT INTO authentication (person_id,appid,appname) VALUES ('{$person->id}','$appid','$appname')");

		// save the API log
		$wwwroot = $this->di->get('path')['root'];
		$logger = new File("$wwwroot/logs/api.log");
		$logger->log("UPDATEAPPID token:$token, appid:$appid, appname:$appname");
		$logger->close();

		// return ok response
		echo '{"code":"ok"}';
	}

	/**
	 * Save a user's appid to contact him/her later via web push notifications
	 *
	 * @author salvipascual
	 *
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
		$person = Utils::getPerson($email);

		// check if the row already exists
		$row = Connection::query("SELECT appid FROM authentication WHERE person_id='{$person->id}' AND appname='apretaste' AND platform='web'");

		// if the row do not exist, create it
		if(empty($row)) {
			Connection::query("INSERT INTO authentication(token,person_id,appid,appname,platform) VALUES ('$token','{$person->id}','$appid','apretaste','web')");
		}
		// if the row exist and the appid is different, update it
		elseif($row[0]->appid != $appid) {
			Connection::query("UPDATE authentication SET appid='$appid', token='$token' WHERE person_id='{$person->id}' AND appname='apretaste' AND platform='web'");
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
	 *
	 * @throws Phalcon\Exception
	 */
	public function checkUserAction($userId)
	{
		if ( ! Utils::isInternalNetwork()) {
			throw new Phalcon\Exception("Access denied for " . php::getClientIP() . " to Api::checkUserAction");
		}

		$this->response->setHeader("Content-type", "application/json");

		echo (Utils::getEmailFromId($userId) === FALSE) ? '{"result": false }' : '{"result": true}';
	}

	/**
	 * Check token
	 *
	 * @param $token
	 * @param $userId
	 *
	 * @throws Phalcon\Exception
	 */
	public function checkTokenAction($userId, $token)
	{
		if ( ! Utils::isInternalNetwork()) {
			throw new Phalcon\Exception("Access denied for " . php::getClientIP() . " to Api::checkUserAction");
		}

		$this->response->setHeader("Content-type", "application/json");

		$security = new Security();
		$user = $security->loginByToken($token);
		echo ($user === false) ? '{"result": false }' : ($user->id === $userId ? '{"result": true}' : '{"result": false}');
	}

	/**
	 * Apretin Bot
	 *
	 * @author kumahacker
	 * @throws Phalcon\Exception
	 */
	public function apretinAction($retoken = '')
	{
		// logger function
		$log = function ($message) {
			$wwwroot = $this->di->get('path')['root'];
			$logger	= new \Phalcon\Logger\Adapter\File("$wwwroot/logs/apretin.log");
			if (!is_array($message)) {
			$message = [$message];
			}
			foreach ($message as $msg) {
			$logger->log($msg);
			}
			$logger->close();
		};

		// get api token and message
		$token		= $this->di->get('config')['telegram']['apretin_token'];
		$security = $this->di->get('config')['telegram']['retoken'];
		$message	= $this->request->getJsonRawBody(TRUE);

		if ($retoken != $security) {
			throw new Phalcon\Exception("Apretin access denied");
		}

		$log([
			"Getting message ",
			json_encode($message),
		]);

		$sendMessage = function ($chat_id, $message, $tk, $replyMarkup = FALSE, $replyTo = FALSE) {
			if ($replyMarkup !== FALSE) {
				if ( ! is_string($replyMarkup)) {
					$replyMarkup = json_encode($replyMarkup);
				}

				$replyMarkup = urlencode($replyMarkup);
			}

			$wwwroot = $this->di->get('path')['root'];
			$url = "https://api.telegram.org/bot{$tk}/sendMessage?chat_id=$chat_id&text=" .
				urlencode($message) . "&parse_mode=HTML" .
				($replyMarkup !== FALSE ? "&reply_markup=$replyMarkup" : "") .
				($replyTo !== FALSE ? '&reply_to_message_id=' . $replyTo : "");

			$results = Utils::file_get_contents_curl($url);
			$logger	= new \Phalcon\Logger\Adapter\File("$wwwroot/logs/apretin.log");
			$logger->log(date("Y-m-d h:i:s\n"));
			$logger->log($url);
			$logger->log("Sending message to telegram @$chat_id: " . substr($message, 0, 40));
			$logger->log("	RESULT: " . json_encode($results));
			$logger->log("\n\n");
			$logger->close();
		};

		if (isset($message['callback_query'])) {
			$message['message'] = $message['callback_query']['message'];
			$message['message']['text'] = $message['callback_query']['data'];
		}

		if (isset($message['message'])) {
			$chat_type = $message['message']['chat']['type'];
			$private = $chat_type == 'private';
			$chat_username = isset($message['message']['chat']['username']) ? $message['message']['chat']['username'] : "";
			$username = $message['message']['from']['username'];
			$chat = $message['message']['chat']['username'];
			$chat_id = $message['message']['chat']['id'];
			$text = $message['message']['text'];
			$message['message']['text'] = Connection::escape($message['message']['text']);

			$log([
				date("Y-m-d h:i:s\n"),
				"Get message " . substr($text, 0, 40) . "from @$username in @$chat",
				"\n\n",
			]);

			// check valid group
			if (array_search($chat_username,['ApretasteCuba', 'apretaste']) && ! $private) {
				$sendMessage($chat_id, "Uff, no me gusta estar aqui...", $token);
				return;
			}

			$new = Connection::query("SELECT username FROM telegram_members WHERE username = '$username';");

			if ( ! isset($new[0])) {
				if (isset($message['message']['new_chat_members'])) {
					foreach ($message['message']['new_chat_members'] as $newMember) {
						$sendMessage($chat_id, "Hola {$newMember['first_name']} {$newMember['last_name']}, te doy la bienvenida a Apretaste. \n\nEste grupo fue creado con el objetivo de intercambiar entre los usuarios de la plataforma Apretaste y sus desarrolladores. La idea es tener un canal alternativo para comunicarnos, compartir ideas, sugerencias, noticias, etc. \n\n Comparte con esta gran familia. ", $token);
					}
				}
			}

			Connection::query("INSERT IGNORE INTO telegram_members (username) VALUES ('$username');");
			Connection::query("INSERT IGNORE INTO telegram_chats (id, title, `type`, username) VALUES ('$chat_id','{$message['message']['chat']['title']}','{$message['message']['chat']['type']}', '$chat_username');");
			Connection::query("
				UPDATE telegram_members SET 
				first_name = '{$message['message']['from']['first_name']}', 
				last_name = '{$message['message']['from']['last_name']}',
				last_access = CURRENT_TIMESTAMP, active = 1 
				WHERE username = '$username';");

			if (isset($message['message']['left_chat_member'])) {
				$leftMember = $message['message']['left_chat_member'];
				$sendMessage($chat_id, "Es triste que te vayas {$leftMember['first_name']} {$leftMember['last_name']}. Esperemos que regreses pronto a compartir con la gran familia de Apretaste.", $token);
				Connection::query("UPDATE telegram_members SET left_date = CURRENT_TIMESTAMP, active = 0;");
			}

			if ($text[0] == '/') {
				$text = substr($text, 1);
				if ( ! $private) {
					$sendMessage($chat_id, "Hola @$username,...", $token, '{
						"inline_keyboard": [[
							{"text":"...vamos por privado", "url": "https://t.me/apretin_bot"}
						]]}');
					return;
				}

			// PIN command
			if (stripos($text, 'pin ') === 0 || stripos($text, 'pin@') === 0) {
				$data_email = trim(substr($text, strpos($text . " ", ' ')));
				if (!empty($data_email)) {
					$sendMessage($chat_id, "Estoy buscando tu PIN asociado a tu $data_email en Apretaste! Dame un chance...", $token);
					$r = Connection::query("SELECT pin FROM person WHERE email = '$data_email' AND pin is not null AND pin <> 0;");

					if (isset($r[0])) {
						$pin = intval($r[0]->pin);
						$res = null;
						$this->sendPIN($data_email, $pin, $res);
						$msg = Connection::escape("Te envie el PIN a $data_email! Revisa tu correo y dime quien eres...");
						$sendMessage($chat_id, $msg, $token,'{
						"inline_keyboard": [[
							{"text":"Soy ...", "callback_data": "/soy email pin"},
							{"text":"Reenviar PIN", "callback_data": "/enlaces"},
							{"text":"No me llega el PIN", "callback_data": "/opciones"}
						]]}');

						Connection::query("
							INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username', 'pin', '{$message['message']['text']}', '$msg', '$chat_id')");
						return;
					}
				}
			}

			// SOY command
			if (stripos($text, 'soy ') === 0 || stripos($text, 'soy@') === 0) {
				$data = trim(substr($text, strpos($text . " ", ' ')));

				if (!empty($data)) {
				while (strpos($data, "	") !== FALSE) {
					$data = str_replace("	", " ", $data);
				}
				$parts = explode(" ", $data);
				if (isset($parts[0]) && isset($parts[1])) {
					$data_email = $parts[0];
					$data_pin	 = intval($parts[1]);

					$sendMessage($chat_id, "Estoy buscando tu cuenta $data_email en Apretaste! Dame un chance...", $token);

					$r = Connection::query("SELECT id, email FROM person WHERE email = '$data_email' AND pin = $data_pin AND pin is not null AND pin <> 0;");

					if (isset($r[0])) {
					Connection::query("UPDATE telegram_members SET id_person = {$r[0]->id} WHERE username = '$username';");
					$msg = Connection::escape("Enhorabuena! Ya se quien eres en Apretaste! Ahora tendremos mejores opciones para ti.");
					$sendMessage($chat_id, $msg, $token);

					$msg = Connection::escape($msg);
					Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','soy', '{$message['message']['text']}', '$msg', '$chat_id')");

					return;
					}
					else {
					$msg = Connection::escape("No encuentro quien eres. Revisa bien tu email y el pin utilizado para autenticarte en la #app de @ApretasteCuba. Recuerda separarlos por un espacio.");
					$sendMessage($chat_id, $msg, $token);
					Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','soy', '{$message['message']['text']}', '$msg', '$chat_id')");

					return;
					}
				}
				}
				$msg = Connection::escape("No entiendo quien eres. Debes escribir tu email y el pin utilizado para autenticarte en la #app de @ApretasteCuba separados por un espacio.");
				$sendMessage($chat_id, $msg, $token);
				Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','soy', '{$message['message']['text']}', '$msg', '$chat_id')");

				return;
			}

			// AUDIENCIA command
			if ($text == "audiencia" || stripos($text, 'audiencia@') === 0) {

				$r	= Connection::query("SELECT count(*) AS total FROM delivery WHERE datediff(current_date, date(request_date)) <= 7;");
				$r1 = Connection::query("SELECT count(*) AS total FROM person WHERE datediff(current_date, date(last_access)) <= 7;");

				$msg = "En los últimos 7 días, <strong>{$r1[0]->total} usuarios</strong> han usado nuestra #app unas <strong>{$r[0]->total} veces</strong>.";
				$sendMessage($chat_id, $msg, $token);

				$msg = Connection::escape($msg);
				Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','audiencia', '{$message['message']['text']}', '$msg', '$chat_id')");
				return;
			}

			// ENALCES command
			if ($text == "enlaces" || stripos($text, 'enlaces@') === 0) {

				$msg = 'Estamos en las redes sociales';

				$sendMessage($chat_id, $msg, $token, '{
									 "inline_keyboard": [
										[
											{"text":"Facebook", "url": "https://www.facebook.com/apretaste/"}, 
											{"text":"Twitter", "url": "https://twitter.com/apretaste"},
											{"text":"Youtube", "url": "https://www.youtube.com/c/Apretaste"}
										]
										]}');

				$msg = Connection::escape($msg);
				Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','enlaces', '{$message['message']['text']}', '$msg', '$chat_id')");

				return;
			}

			// START command
			if ($text == "start" || stripos($text, 'start@') === 0) {
				$msg = "Hola soy Apretín, el bot de @ApretasteCuba. En que puedo ayudarte.";
				$sendMessage($chat_id, $msg, $token);
				$msg = Connection::escape($msg);
				Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','start', '{$message['message']['text']}', '$msg', '$chat_id')");
				return;
			}

			// APP command
			if ($text == "app" || stripos($text, 'app@') === 0) {
				$sendMessage($chat_id, "Descarga nuestra #app desde Play Store\n https://play.google.com/store/apps/details?id=com.apretaste.apretaste", $token);
				return;
			}

			// OPCIONES command
			if ($text == "opciones" || stripos($text, 'opciones@') === 0) {
				$msg = "Opciones de Apretin";
				$sendMessage($chat_id, $msg, $token, '{
				"inline_keyboard": [
				[
					{"text":"Audiencia", "callback_data": "/audiencia"},
					{"text":"Enlaces", "callback_data": "/enlaces"},
					{"text":"Descarga la app", "callback_data": "/app"},
					{"text":"Enlazar con A!", "callback_data": "/soy"},
					{"text":"Recibir PIN", "callback_data": "/pin"}
				]
				]}');

				$msg = Connection::escape($msg);
				Connection::query("INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
							VALUES ('$username','opciones', '{$message['message']['text']}', '$msg', '$chat_id')");

				return;
			}

			// Admin Commands
			if (stripos($text, "admin:sql ") === 0 && $username === 'kumahacker') {
				$sql = trim(substr($text, stripos($text, ' ')));

				$result = @Connection::query($sql);

				if (!is_array($result)) {
				$sendMessage($chat_id, "No results", $token);
				return;
				}

				$output = '<pre>';
				if (is_array($result)) {
				$first = TRUE;
				foreach ($result as $row) {
					if (is_object($row)) {
					$row = get_object_vars($row);
					}
					if ($first) {
					$output .= str_repeat("-", 50) . "\n";
					foreach ($row as $field => $value) {
						$output .= $field . "\t";
					}
					$output .= "\n" . str_repeat("-", 50) . "\n";
					}

					foreach ($row as $field => $value) {
					$output .= $value . " \t";
					}

					$output .= "\n";
					$first	= FALSE;
				}
				}

				$output .= '</pre>';
				$sendMessage($chat_id, $output, $token);
				return;
			}

			$sendMessage($chat_id, "Lo siento @$username, pero no entendi que quisiste decir.", $token);
			}

			$bad_words = explode(',', 'pinga,cojone,pingon,maricon,puta,singao,hijo de puta');
			$send			= FALSE;
			foreach ($bad_words as $word) {
			if (stripos($text, $word) !== FALSE) {
				$send = TRUE;
				break;
			}
			}

			if ($send) {
			if (stripos($text, 'apretin') !== FALSE) {
				$sendMessage($chat_id, "Oye @$username, a mi me hablas bien!", $token);
			}
			else {
				$sendMessage($chat_id, "Hey @$username, habla bonito...", $token);
			}
			}

			Connection::query("
				INSERT INTO telegram_apretin (username, command, received_message, sent_message, chat_id)
				VALUES ('$username','unknown', '{$message['message']['text']}', '', '$chat_id')");

		}
	}
}
