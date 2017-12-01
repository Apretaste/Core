<?php

class PushNotification
{
	/**
	 * Get the ID of the app to send a push notification
	 *
	 * @author salvipascual
	 * @param String $email
	 * @param String $appname
	 * @return String or false
	 */
	public function getAppId($email, $appname)
	{
		$connection = new Connection();
		$appid = $connection->query("SELECT appid FROM authentication WHERE email='$email' AND appname='$appname'");

		if(empty($appid)) return false;
		return $appid[0]->appid;
	}

	/**
	 * Update a new appid by another
	 *
	 * @author salvipascual
	 * @param String
	 * @param String
	 * @param String
	 */
	public function setAppId($email, $appid, $appname)
	{
		$connection = new Connection();
		$connection->query("UPDATE authentication SET appid='$appid' WHERE email='$email' AND appname='$appname'");
	}

	/**
	 * Get a person based on an AppId
	 *
	 * @author salvipascual
	 * @param String
	 * @return Person
	 */
	public function getPersonFromAppId($appid)
	{
		$connection = new Connection();
		$email = $connection->query("SELECT email FROM authentication WHERe appid='$appid'");
		if(empty($email)) return false;

		// return the person object
		$utils = new Utils();
		return $utils->getPerson($email[0]->email);
	}

	/**
	 * Send a text only push notification
	 *
	 * @author salvipascual
	 * @param String $title
	 * @param String $body
	 * @return JSON Response
	 */
	public function sendTextPush($appid, $title, $body)
	{
		// prepare de data structure
		$data = array (
			'title' => $title,
			'body' => $body,
			'subtitle' => '',
			'tickerText' => '',
			'vibrate' => 1,
			'sound'  => 1,
			'largeIcon' => 'large_icon',
			'smallIcon' => 'small_icon',
			'notification_type' => '');

		// call the general push
		$this->sendGeneralAppPush($appid, $data);
	}

	/**
	 * Send a push notification for chat in piropazo
	 *
	 * @author salvipascual
	 * @param String|Array $appid
	 * @param Person $from
	 * @param Person $to
	 * @param String $message
	 * @return JSON Response
	 */
	public function piropazoChatPush($appid, $from, $to, $message)
	{
		// prepare de data structure
		$data = array(
			"title" => $from->full_name,
			"body" => "@{$from->username}: $message",
			"notification_type" => "chat_notification",
			"message_data" => array(
				"from_username" => $from->username,
				"from_user_fullname" => $from->full_name,
				"from_user_image" => $from->picture_public,
				"from_user_gender" => $from->gender,
				"to_user_fullname" => $to->full_name,
				"to_user_image" => $to->picture_public,
				"to_user_gender" => $to->gender,
				"message" => $message
			)
		);

		// call the general push
		return $this->sendGeneralAppPush($appid, $data);
	}

	/**
	 * Send a push notification for like in piropazo
	 *
	 * @author salvipascual
	 * @param String|Array $appid
	 * @param Person $person
	 * @return JSON Response
	 */
	public function piropazoLikePush($appid, $person)
	{
		// get the person who will receive the push
		$personReceiver = $this->getPersonFromAppId($appid);

		// translate the message
		if($personReceiver && $personReceiver->lang == "en") $body = "@{$person->username} likes your profile";
		else $body = "A @{$person->username} le ha gustado su perfil";

		// prepare de data structure
		$data = array (
			"title" => $person->full_name,
			"body" => $body,
			"notification_type" => "like_notification",
			"like_data" => array(
				"from_username" => $person->username,
				"from_user_fullname" => $person->full_name,
				"from_user_image" => $person->picture_public,
				"from_user_description" => $person->about_me
			)
		);

		// call the general push
		return $this->sendGeneralAppPush($appid, $data);
	}

	/**
	 * Send a push notification for flower in piropazo
	 *
	 * @author salvipascual
	 * @param String|Array $appid
	 * @param Person $person
	 * @return JSON Response
	 */
	public function piropazoFlowerPush($appid, $person)
	{
		// get the person who will receive the push
		$personReceiver = $this->getPersonFromAppId($appid);

		// translate the message
		if($personReceiver && $personReceiver->lang == "en")
		{
			$header = "Hurray, @{$person->username} sent you a flower";
			$text = "@{$person->username} has seen something different in you, and this flower it is a call to chat and get to know each other better. Would you accept?";
		}
		else
		{
			$header = "Enhorabuena, @{$person->username} le ha mandado una flor";
			$text = "@{$person->username} ha visto algo diferente en tí, y con esta flor te deja saber que le encantaría chatear contigo y conocerse mejor. ¿Aceptarías?";
		}

		// prepare de data structure
		$data = array (
			"title" => $person->full_name,
			"body" => $header,
			"notification_type" => "flower_notification",
			"flower_data" => array(
				"from_username" => $person->username,
				"from_user_fullname" => $person->full_name,
				"from_user_image" => $person->picture_public,
				"from_user_description" => $text
			)
		);

		// call the general push
		return $this->sendGeneralAppPush($appid, $data);
	}

	/**
	 * Send a web push notification when you receive a chat at Pizarra
	 *
	 * @author salvipascual
	 * @param String $appid, id to send the notification
	 * @param String $from, @username of the sender
	 * @param String $text, text sent
	 * @return Boolean
	 */
	public function pizarraChatReceived($appid, $from, $text)
	{
		// prepara params to send
		$title = "@$from le ha escrito una nota";
		$message = substr($text, 0, 80);
		$callbackUrl = "https://pizarracuba.com/chats/with/$from";

		// call the general web push
		return $this->sendGeneralWebPush($appid, $title, $message, $callbackUrl);
	}

	/**
	 * Send a web push notification when somebody mentions you
	 *
	 * @author salvipascual
	 * @param String $appid, id to send the notification
	 * @param String $from, @username of the sender
	 * @return Boolean
	 */
	public function pizarraUserMentioned($appid, $from)
	{
		// prepara params to send
		$title = "@$from le mencionó en público";
		$message = "Haga click aquí para ver que @$from escribió de usted";
		$callbackUrl = "https://pizarracuba.com/feed"; //@TODO point to a unique note

		// call the general web push
		return $this->sendGeneralWebPush($appid, $title, $message, $callbackUrl);
	}

	/**
	 * Send a web push notification when somebody adds a heart to your post
	 *
	 * @author salvipascual
	 * @param String $appid, id to send the notification
	 * @param String $from, @username of the sender
	 * @param String $text, text sent
	 * @return Boolean
	 */
	public function pizarraHeartNote($appid, $from, $note)
	{
		// prepara params to send
		$title = "A @$from le gustó su nota";
		$message = substr($note, 0, 80);
		$callbackUrl = "https://pizarracuba.com/feed"; //@TODO point to a unique note

		// call the general web push
		return $this->sendGeneralWebPush($appid, $title, $message, $callbackUrl);
	}

	/**
	 * Send a push notification for a phone
	 *
	 * @author salvipascual
	 * @param Array|String $appid, IDs to push
	 * @param Array $data, structure to send
	 * @return JSON Response
	 */
	private function sendGeneralAppPush($appid, $data)
	{
		// get the server key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$serverKey = $di->get('config')['firebase']['serverkey'];

		// prepare de message
		$fields = array(
			'to' => $appid,
			'data' => $data, // Android
			'notification' => $data, // IOS
			'priority'=>'high'
		);

		// prepare the server auth
		$headers = array(
			'Authorization:key='.$serverKey,
			'Content-Type: application/json'
		);

		// send the push notification
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);

		// handle errors sending push and close connection
		if ($result === false) error_log('Error Pushing Notification: ' . curl_error($ch));
		curl_close($ch);

		// save the API log
		$wwwroot = $di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("NEW APP PUSH: ".json_encode($data));
		$logger->close();

		// return the result
		return $result;
	}

	/**
	 * Send a push notification for the web
	 *
	 * @author salvipascual
	 * @param String $appid, ID to push
	 * @param String $title, title of the push
	 * @param String $message, message of the push
	 * @param String $callbackUrl, url to send the user
	 * @return Boolean
	 */
	private function sendGeneralWebPush($appid, $title, $message, $callbackUrl)
	{
		// get the server key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$apiToken = $di->get('config')['pushcrew']['apitoken'];

		// set POST variables
		$fields = array(
			'title' => $title,
			'message' => $message,
			'url' => $callbackUrl,
			'subscriber_id' => $appid
		);

		// contact the API using curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://pushcrew.com/api/v1/send/individual/');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:key='.$apiToken));
		$json = curl_exec($ch);
		curl_close($ch);

		// save the API log
		$wwwroot = $di->get('path')['root'];
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/api.log");
		$logger->log("NEW WEB PUSH: $json");
		$logger->close();

		// return boolean if susccess
		$res = json_decode($json, true);
		return $res['status'] == 'success';
	}
}
