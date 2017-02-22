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
	 * Send a push notification
	 *
	 * @author salvipascual
	 * @param String
	 * @param String
	 * @return String
	 */
	public function sendPush($appids, $title, $body)
	{
		// appids must be a array
		$appids = explode(",", $appids);

		// get the server key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$serverKey = $di->get('config')['firebase']['serverkey'];

		// prepare de message
		$fields = array(
			'registration_ids' => $appids,
			'data' => array (
				'title' => $title,
				'body' => $body,
				'subtitle' => '',
				'tickerText' => '',
				'vibrate' => 1,
				'sound'  => 1,
				'largeIcon' => 'large_icon',
				'smallIcon' => 'small_icon'
			)
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
