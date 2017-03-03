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
		$appid = $connection->deepQuery("SELECT appid FROM authentication WHERE email='$email' AND appname='$appname'");

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
		$connection->deepQuery("UPDATE authentication SET appid='$appid' WHERE email='$email' AND appname='$appname'");
	}

	/**
	 * Send a text only push notification
	 *
	 * @author salvipascual
	 * @param String $title
	 * @param String $body
	 * @return JSON Response
	 */
	public function sendTextPush($appids, $title, $body)
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
		$this->sendGeneralPush($appids, $data);
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
	public function piropazoChatPush($appids, $from, $to, $message)
	{
		// prepare de data structure
		$data = array(
			"title" => $from->full_name,
			"body" => $message,
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
		return $this->sendGeneralPush($appids, $data);
	}

	/**
	 * Send a push notification for like in piropazo
	 *
	 * @author salvipascual
	 * @param String|Array $appid
	 * @param Person $person
	 * @return JSON Response
	 */
	public function piropazoLikePush($appids, $person)
	{
		// translate message
		if($person->lang == "en") $body = "@{$person->username} likes your profile";
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
		return $this->sendGeneralPush($appids, $data);
	}

	/**
	 * Send a push notification for flower in piropazo
	 *
	 * @author salvipascual
	 * @param String|Array $appid
	 * @param Person $person
	 * @return JSON Response
	 */
	public function piropazoFlowerPush($appids, $person)
	{
		// translate message
		if($person->lang == "en") $body = "Hurray, @{$person->username} sent you a flower. Act now, this means he/she really likes you.";
		else $body = "Enhorabuena, @{$person->username} le ha mandado una flor. Este es un sintoma inequivoco de le gustas.";

		// prepare de data structure
		$data = array (
			"title" => $person->full_name,
			"body" => $body,
			"notification_type" => "flower_notification",
			"flower_data" => array(
				"from_username" => $person->username,
				"from_user_fullname" => $person->full_name,
				"from_user_image" => $person->picture_public,
				"from_user_description" => $person->about_me
			)
		);

		// call the general push
		return $this->sendGeneralPush($appids, $data);
	}

	/**
	 * Send a push notification
	 *
	 * @author salvipascual
	 * @param Array|String $appids, IDs to push
	 * @param Array $data, structure to send
	 * @return JSON Response
	 */
	private function sendGeneralPush($appids, $data)
	{
		// appids must be a array
		$appids = explode(",", $appids);

		// get the server key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$serverKey = $di->get('config')['firebase']['serverkey'];

		// prepare de message
		$fields = array(
			'registration_ids' => $appids,
			'data' => $data
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

		// return the result
		return $result;
	}
}
