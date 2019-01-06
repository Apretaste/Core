<?php

use G4\Crypto\Crypt;
use G4\Crypto\Adapter\OpenSSL;
use Phalcon\DI\FactoryDefault;

class Utils
{
	/**
	 * Returns a valid Apretaste email to send an email
	 *
	 * @author salvipascual
	 * @return String, email address
	 */
	public static function getValidEmailAddress()
	{
		// get the current environment
		$di = FactoryDefault::getDefault();
		$environment = $di->get('environment');

		// get a random mailbox
		$node = Connection::query("
			SELECT email FROM delivery_input
			WHERE environment='$environment' AND active=1
			ORDER BY RAND() LIMIT 1");

		// return the default email
		if(empty($node)) return "apretaste@gmail.com";

		// add alias to the email
		$name = $node[0]->email;
		return "$name@gmail.com";
	}

	/**
	 * Returns an email address to contact the customer support
	 *
	 * @author salvipascual
	 * @return String, email address
	 */
	public static function getSupportEmailAddress()
	{
		// get a random support email
		$support = Connection::query("
			SELECT email FROM delivery_input
			WHERE environment='support' AND active=1
			ORDER BY RAND() LIMIT 1");

		// alert if no support mailbox
		if(empty($support)) self::createAlert("No support email in table delivery_input", "ERROR");
		else $support = $support[0]->email;

		return "$support@gmail.com";
	}

	/**
	 * Format a link to be an Apretaste mailto
	 *
	 * @author salvipascual
	 * @param mixed $service name of the service
	 * @param mixed $subservice name of the subservice, if needed
	 * @param mixed $parameter pharse to search, if needed
	 * @param mixed $body body of the email, if necessary
	 * @return String link to add to the href section
	 */
	public static function getLinkToService($service, $subservice = false, $parameter = false, $body = false)
	{
		$link = "mailto:".self::getValidEmailAddress()."?subject=".strtoupper($service);
		if ($subservice) $link .= " $subservice";
		if ($parameter) $link .= " $parameter";
		if ($body) $link .= "&body=$body";
		return $link;
	}

	/**
	 * Check if the service exists and returns its real name
	 *
	 * @author salvipascual
	 * @param String $name, name or alias of the service
	 * @return String, name of service or false if not exist
	 */
	public static function serviceExist($name){
		// if serviceName is an alias get the service name
		$r = Connection::query("SELECT * FROM service WHERE name = '$name';");

		// check if service exist and return its name
		$di = FactoryDefault::getDefault();
		$www_root = $di->get('path')['root'];
		if(isset($r[0]) && file_exists("$www_root/services/$name/service.php")) return $name;
		else return false;
	}

	/**
	 * Check if a user exists and get the profile
	 *
	 * @author salvipascual
	 * @return object|boolean
	 */
	public static function getPerson($email){
		// get the person via email
		$person = Connection::query("SELECT * FROM person WHERE LOWER(email)=LOWER('$email')");
		return $person ? $person[0] : false;
	}

	/**
	 * Create a unique username using the email
	 *
	 * @author salvipascual
	 * @version 3.0
	 * @param String $email
	 * @return String, username
	 */
	public static function usernameFromEmail($email)
	{
		// get the first part of the username
		$shortmail = strtolower(preg_replace('/[^A-Za-z]/', '', $email)); // remove special chars and caps
		$shortmail = substr($shortmail, 0, 7); // get the first 7 chars

		// contatenate a random unique number
		do {
			$username = $shortmail . rand(1, 999);
			$exist = Connection::query("SELECT id FROM person WHERE username='$username'");
		} while($exist);

		return $username;
	}

	/**
	 * Get the email from an username
	 *
	 * @author salvipascual
	 * @param String $username
	 * @return String email or false
	 */
	public static function getEmailFromUsername($username)
	{
		// do not try empty inputs
		if(empty($username)) return false;

		// remove the @ symbol
		$username = str_replace("@", "", $username);

		// get the email
		$email = Connection::query("SELECT email FROM person WHERE username='$username'");

		// return the email or false if not found
		if(empty($email)) return false;
		else return $email[0]->email;
	}

	/**
	 * Get the email from an id
	 *
	 * @author salvipascual
	 * @param Int $id
	 * @return String email or false
	 */
	public static function getEmailFromId($id)
	{
		// do not try empty inputs
		if(empty($id)) return false;

		// get the email
		$email = Connection::query("SELECT email FROM person WHERE id=$id");

		// return the email or false if not found
		if(empty($email)) return false;
		else return $email[0]->email;
	}

	/**
	 * Get the id from an username
	 *
	 * @author salvipascual
	 * @param String $username
	 * @return Int id or false
	 */
	public static function getIdFromUsername($username)
	{
		// do not try empty inputs
		if(empty($username)) return false;

		// remove the @ symbol
		$username = str_replace("@", "", $username);

		// get the email
		$id = Connection::query("SELECT id FROM person WHERE username='$username'");

		// return the id or false if not found
		if(empty($id)) return false;
		else return $id[0]->id;
	}

	/**
	 * Get the username from an email
	 *
	 * @author salvipascual
	 * @param String $email
	 * @return String username or false
	 */
	public static function getUsernameFromEmail($email)
	{
		// get the username

		$username = Connection::query("SELECT username FROM person WHERE email='$email'");

		// return the email or false if not found
		if(empty($username)) return false;
		else return $username[0]->username;
	}

	/**
	 * Get the path to a service.
	 *
	 * @author salvipascual
	 * @param String $serviceName, name of the service to access
	 * @return String, path to the service, or false if the service do not exist
	 */
	public static function getPathToService($serviceName){
		// get the path to service
		$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
		$path = "$wwwroot/services/$serviceName";

		// check if the path exist and return it
		if(file_exists($path)) return $path;
		else return false;
	}

	/**
	 * Return the current Raffle or false if no Raffle was found
	 *
	 * @author salvipascual
	 *
	 * @return array|boolean
	 */
	public static function getCurrentRaffle()
	{
		// get the raffle
		$raffle = Connection::query("SELECT * FROM raffle WHERE CURRENT_TIMESTAMP BETWEEN start_date AND end_date");

		// return false if there is no open raffle
		if (count($raffle)==0) return false;
		else $raffle = $raffle[0];

		// get number of tickets opened
		$openedTickets = Connection::query("SELECT count(ticket_id) as opened_tickets FROM ticket WHERE raffle_id is NULL");
		$openedTickets = $openedTickets[0]->opened_tickets;

		// get the image of the raffle
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$raffleImage = "$wwwroot/public/raffle/" . md5($raffle->raffle_id) . ".jpg";

		// add elements to the response
		$raffle->tickets = $openedTickets;
		$raffle->image = $raffleImage;

		return $raffle;
	}

	/**
	 * Generate a new random hash. Mostly to be used for temporals
	 *
	 * @author salvipascual
	 * @return String
	 */
	public static function generateRandomHash()
	{
		$rand = rand(0, 1000000);
		$today = date('full');
		return md5($rand . $today);
	}

	/**
	 * Reduce image size and optimize the image quality
	 *
	 * @author salvipascual
	 * @author kuma
	 * @version 2.0
	 * @param String $fromPath, path to load the image
	 * @param String $toPath, path to save the image
	 * @param mixed $quality Decrease/increase quality
	 * @return boolean
	 */
	public static function optimizeImage($fromPath, &$toPath=false, $quality=50)
	{
		// do not accept non-existing images
		if( ! file_exists($fromPath)) return false;

		// get path to save and extensions
		if(empty($toPath)) $toPath = $fromPath;
		$fromExt = pathinfo($fromPath, PATHINFO_EXTENSION);
		$toExt = pathinfo($toPath, PATHINFO_EXTENSION);

		if($toExt == 'webp') {
			// convert valid files to webp and optimize
			if(in_array($fromExt, ['jpg','jpeg','png'])) {
				shell_exec("cwebp $fromPath -q $quality -o $toPath");
				return true;
			// for invalid files, change ext and optimize via SimpleImage
			}else{
				$toPath = rtrim($toPath, $toExt) . $fromExt;
				$toExt = $fromExt;
			}
		}

		// include SimpleImage class
		$di = FactoryDefault::getDefault();
		require_once $di->get('path')['root']."/lib/SimpleImage.php";

		// optimize image
		try {
			$img = new \abeautifulsite\SimpleImage();
			$img->load($fromPath);
			$img->save($toPath, $quality, $toExt);
		} catch (Exception $e) {
			self::createAlert("[Utils::optimizeImage] EXCEPTION: ".Debug::getReadableException($e));
			return false;
		}

		return true;
	}

	/**
	 * Get the pieces of names from the full name
	 *
	 * @author hcarras
	 * @param String $name, full name
	 * @return array [$firstName, $middleName, $lastName, $motherName]
	 */
	public static function fullNameToNamePieces($name)
	{
		$namePieces = explode(" ", $name);
		$newNamePieces = [];
		$tmp = "";

		foreach ($namePieces as $piece)
		{
			$tmp .= "$piece ";
			if(in_array(strtoupper($piece), array("DE","LA","Y","DEL"))) continue;
			else
			{
				$newNamePieces[] = $tmp;
				$tmp = "";
			}
		}

		$firstName = "";
		$middleName = "";
		$lastName = "";
		$motherName = "";

		if(count($newNamePieces)>=4)
		{
			$firstName = $newNamePieces[0];
			$middleName = $newNamePieces[1];
			$lastName = $newNamePieces[2];
			$motherName = $newNamePieces[3];
		}

		if(count($newNamePieces)==3)
		{
			$firstName = $newNamePieces[0];
			$lastName = $newNamePieces[1];
			$motherName = $newNamePieces[2];
		}

		if(count($newNamePieces)==2)
		{
			$firstName = $newNamePieces[0];
			$lastName = $newNamePieces[1];
		}

		if(count($newNamePieces)==1)
		{
			$firstName = $newNamePieces[0];
		}

		$firstName = str_replace("'", "", $firstName);
		$middleName = str_replace("'", "", $middleName);
		$lastName = str_replace("'", "", $lastName);
		$motherName = str_replace("'", "", $motherName);

		return array($firstName, $middleName, $lastName, $motherName);
	}

	/**
	 * Return path to the temporal folder
	 *
	 * @author salvipascual
	 * @return string
	 */
	public static function getTempDir(){
		return FactoryDefault::getDefault()->get('path')['root'] . "/temp/";
	}

	/**
	 * Return path to the public temp folder
	 *
	 * @author salvipascual
	 * @param Enum $path root|http
	 * @return string
	 */
	public static function getPublicTempDir($path='root')
	{
		$di = FactoryDefault::getDefault();
		$wwwroot = $di->get('path')[$path];

		if($path == 'root') return "$wwwroot/public/temp/";
		elseif($path == 'http') return "$wwwroot/temp/";
		else return false;
	}

	/**
	 * Check token and retrieve the user that is logged
	 *
	 * @author salvipascual
	 * @param String token
	 * @return String email OR false
	 */
	public static function detokenize($token)
	{
		$auth = Connection::query("SELECT email FROM person WHERE token='$token'");
		if(empty($auth)) return false;
		return $auth[0]->email;
	}

	/**
	 * Clear string
	 *
	 * @param String $name
	 * @return String
	 */
	public static function clearStr($name, $extra_chars = '', $chars = "abcdefghijklmnopqrstuvwxyz")
	{
		$l = strlen($name);
		$newname = '';
		$chars .= $extra_chars;

		for ($i = 0; $i < $l; $i++) {
			$ch = $name[$i];
			if (stripos($chars, $ch) !== false) $newname .= $ch;
		}

		return $newname;
	}

	/**
	 * Extract emails from text
	 *
	 * @param string $text
	 * @return mixed
	 */
	public static function getEmailFromText($text)
	{
		$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
		preg_match($pattern, $text, $matches);

		if ( ! empty($matches))
			return $matches[0];
			else
				return false;
	}

	/**
	 * Extract cell phone numbers from text
	 *
	 * @param string $text
	 * @return mixed
	 */
	public static function getCellFromText($text)
	{
		$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
		$pattern = "/5(2|3)\d{6}/"; // every 8 digits numbers starting by 52 or 53
		preg_match($pattern, $cleanText, $matches);

		if ( ! empty($matches))
			return $matches[0];
			else
				return false;
	}

	/**
	 * Extact phone numbers from text
	 *
	 * @param string $text
	 * @return mixed
	 */
	public static function getPhoneFromText($text)
	{
		$cleanText = preg_replace('/[^A-Za-z0-9\-]/', '', $text); // remove symbols and spaces
		$pattern = "/(48|33|47|32|7|31|47|24|45|23|42|22|43|21|41|46)\d{6,7}/";
		preg_match($pattern, $cleanText, $matches);

		if ( ! empty($matches))
			return $matches[0];
			else
				return false;
	}

	/**
	 * Convert file size to friendly message
	 *
	 * @param integer $size
	 * @return string
	 */
	public static function getFriendlySize($size)
	{
		$unit = ['b','kb','mb','gb','tb','pb'];
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}

	/**
	 * Detect province from phone number
	 *
	 * @param string $phone
	 * @return string
	 */
	public static function getProvinceFromPhone($phone)
	{
		if (strpos($phone, "7") == 0) return 'LA_HABANA';
		if (strpos($phone, "21") == 0) return 'GUANTANAMO';
		if (strpos($phone, "22") == 0) return 'SANTIAGO_DE_CUBA';
		if (strpos($phone, "23") == 0) return 'GRANMA';
		if (strpos($phone, "24") == 0) return 'HOLGUIN';
		if (strpos($phone, "31") == 0) return 'LAS_TUNAS';
		if (strpos($phone, "32") == 0) return 'CAMAGUEY';
		if (strpos($phone, "33") == 0) return 'CIEGO_DE_AVILA';
		if (strpos($phone, "41") == 0) return 'SANCTI_SPIRITUS';
		if (strpos($phone, "42") == 0) return 'VILLA_CLARA';
		if (strpos($phone, "43") == 0) return 'CIENFUEGOS';
		if (strpos($phone, "45") == 0) return 'MATANZAS';
		if (strpos($phone, "46") == 0) return 'ISLA_DE_LA_JUVENTUD';
		if (strpos($phone, "47") == 0) return 'ARTEMISA';
		if (strpos($phone, "47") == 0) return 'MAYABEQUE';
		if (strpos($phone, "48") == 0) return 'PINAR_DEL_RIO';
	}

	/**
	 * Insert a notification in the database
	 *
	 * @author kumahacker
	 * @param string $email
	 * @param string $origin
	 * @param string $text
	 * @param string $link
	 * @param string $tag
	 * @return array
	 */
	public static function addNotification($id, $origin, $text, $link='', $tag='INFO')
	{
		// get the person's numeric ID
		$id = strpos($email,'@')?self::personExist($email):$email;
		$email = strpos($email,'@')?$email:self::getEmailFromId($id);

		// check if we should send a web push
		$row = Connection::query("SELECT appid FROM authentication WHERE person_id='$id' AND appname='apretaste' AND platform='web'");
		$ispush = empty($row[0]->appid) ? 0 : 1;

		// if the person has a valid appid, send a web push
		if($ispush)
		{
			$di = FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];
			$wwwhttp = $di->get('path')['http'];

			// convert the link to URL
			$token = self::detokenize($email);
			$tokenStr = $token ? "&token=$token" : "";
			$url = empty($link) ? "" : "$wwwhttp/run/display?subject=$link{$tokenStr}";

			// get the image for the service
			$service = strtolower($origin);
			$img = file_exists("$wwwroot/public/temp/$service.png") ? "$wwwhttp/temp/$service.png" : "";

			// create title and message
			$title = ucfirst($origin);
			$message = substr($text, 0, 80);

			// send web push notification for users of the web
			$pushNotification = new PushNotification();
			$pushNotification->sendWebPush($row[0]->appid, $title, $message, $url, $img);
		}

		// increase number of notifications
		Connection::query("UPDATE person SET notifications = notifications+1 WHERE id=$id");

		// insert notification in the db and get id
		return Connection::query("INSERT INTO notifications (id_person, email, origin, `text`, link, tag, ispush) VALUES ($id_person,'$email','$origin','$text','$link','$tag','$ispush')");
	}

	/**
	 * Return the number of notifications for a user
	 *
	 * @param string $id_person
	 * @return integer
	 */
	public static function getNumberOfNotifications($id_person)
	{
		// temporal mechanism?
		$r = Connection::query("SELECT notifications FROM person WHERE notifications is null AND id = $id_person");
		if ( ! isset($r[0])) {
			$r[0] = new stdClass();
			$r[0]->notifications = '';
		}

		$notifications = $r[0]->notifications;
		if (trim($notifications) == '') {
			// calculate notifications and update the number
			$r = Connection::query("SELECT count(id_person) as total FROM notifications WHERE id_person = $id_person AND viewed = 0;");
			$notifications = $r[0]->total * 1;
			Connection::query("UPDATE person SET notifications = $notifications WHERE id = $id_person");
		}

		return $notifications * 1;
	}

	/**
	 * Return a list of notifications and mark as seen
	 *
	 * @author salvipascual
	 * @param Integer $personId
	 * @param Integer $limit
	 * @param String[] $origin, list of services IE [pizarra,nota,chat]
	 * @return array
	 */
	public static function getNotifications($personId, $limit=20, $origin=[])
	{
		// get origins SQL if passed
		$services = "";
		if( ! empty($origin)) {
			$temp = [];
			foreach ($origin as $o) $temp[] = "origin LIKE '$o%'";
			$services = implode(" OR ", $temp);
			$services = "AND ($services)";
		}

		// create SQL to get notifications
		$notifications = Connection::query("
			SELECT id_person, origin, inserted_date, text, viewed, viewed_date, link, tag, ispush
			FROM notifications
			WHERE id_person = $personId
			$services
			ORDER BY inserted_date DESC
			LIMIT $limit");

		// mark all notifications as seen
		if($notifications) {
			Connection::query("UPDATE notifications SET viewed=1, viewed_date=CURRENT_TIMESTAMP WHERE id_person=$personId");
		}

		return $notifications;
	}

	/**
	 * Encript a message using the user's public key.
	 *
	 * @author salvipascual
	 *
	 * @param String $text
	 *
	 * @return String
	 * @throws Exception
	 */
	public static function encrypt($text)
	{
		// get the seed from the config file
		$di = FactoryDefault::getDefault();
		$seed = $di->get('config')['global']['seed'];

		// configure crypto with SSL
		$crypto = new Crypt(new OpenSSL());
		$crypto->setEncryptionKey($seed);

		// encript message
		return $crypto->encode($text);
	}

	/**
	 * Decript a message using the user's private key.
	 * The message should be encrypted with RSA OAEP 1024 bits and passed in String Base 64.
	 *
	 * @author salvipascual
	 *
	 * @param String $text
	 *
	 * @return String
	 * @throws Exception
	 */
	public static function decrypt($text)
	{
		// get the seed from the config file
		$di = FactoryDefault::getDefault();
		$seed = $di->get('config')['global']['seed'];

		// configure crypto with SSL
		$crypto = new Crypt(new OpenSSL());
		$crypto->setEncryptionKey($seed);

		// decript message
		return $crypto->decode($text);
	}

	/**
	 * Regenerate a sentense with random Spanish words
	 *
	 * @author salvipascual
	 * @param Integer $count, number of words selected
	 * @return String
	 */
	public static function randomSentence($count=-1)
	{
		// get the number of words when no param passed
		if ($count == -1 || $count == 0) $count = rand(2, 10);

		// list of possible words to select
		$words = array("abajo","abandonar","abrir","abrir","absoluto","abuelo","acabar","acabar","acaso","accion","aceptar","aceptar","acercar","acompanar","acordar","actitud","actividad","acto","actual","actuar","acudir","acurdo","adelante","ademas","adquirir","advertir","afectar","afirmar","agua","ahora","aire","alcanzar","lcanzar","alejar","aleman","algo","alguien","alguno","algun","alla","alli","alma","alto","altura","amr","ambos","americano","amigo","amor","amplio","anadir","analisis","andar","animal","ante","anterior","antes","antiguo","anunciar","aparecer","aparecer","apenas","aplicar","apoyar","aprender","aprovechar","aquel","aquello","aqui","arbol","arma","arriba","arte","asegurar","asi","aspecto","asunto","atencio","atras","atreverse","aumentar","aunque","autentico","autor","autoridad","avanzar","ayer","ayuda","audar","ayudar","azul","bajar","bajo","barcelona","barrio","base","bastante","bastar","beber","bien","lanco","boca","brazo","buen","buscar","buscar","caballo","caber","cabeza","cabo","cada","cadena","cae","caer","calle","cama","cambiar","cambiar","cambio","caminar","camino","campana","campo","cantar","cntidad","capacidad","capaz","capital","cara","caracter","carne","carrera","carta","casa","casar","cas","caso","catalan","causa","celebrar","celula","central","centro","cerebro","cerrar","ciones","comenzr","como","comprender","conocer","conseguir","considerar","contar","convertir","correr","crear","cree","cumplir","deber","decir","dejar","descubrir","dirigir","empezar","encontrar","entender","entrar","scribir","escuchar","esperar","estar","estudiar","existir","explicar","formar","ganar","gustar","habe","hablar","hacer","intentar","jugar","leer","levantar","llamar","llegar","llevar","lograr","mana","mntener","mirar","nacer","necesitar","ocurrir","ofrecer","paces","pagar","parecer","partir","prtir","pasar","pedir","pensar","perder","permitir","plia","poder","poner","preguntar","presentar","prducir","quedar","querer","racteres","realizar","recibir","reconocer","recordar","resultar","saber","scar","salir","seguir","sentir","servir","suponer","tener","terminar","tocar","tomar","trabajar","trae","tratar","traves","utilizar","venir","vivir","volver");

		// get the sentence
		$sentence = [];
		for ($i=0; $i<$count; $i++)
		{
			$pos = rand(1, count($words));
			$sentence[] = $words[$pos-1];
		}

		// return the actual sentence
		return implode(" ", $sentence);
	}

	/**
	 * Get the tracking handle
	 *
	 * @author salvipascual
	 * @param String $email, in the form salvi_t{handle}@nauta.cu
	 * @return String, tracking handle
	 */
	public static function getCampaignTracking($email)
	{
		// if it is not a campaign, return false
		if (strpos($email, '_t') == false) return false;

		// get the handle
		$responseData = explode("_t", $email);
		$responseData = explode("@", $responseData[1]);
		$handle = explode("@", $responseData[0]);

		// return the handle if exist
		if( ! $handle) return false;
		return $handle[0];
	}

	/**
	 * Add a new subscriber to the email list
	 *
	 * @author salvipascual
	 * @param String email
	 */
	public static function subscribeToEmailList($email)
	{

		Connection::query("UPDATE person SET mail_list=1 WHERE email='$email'");
	}

	/**
	 * Delete a subscriber from the email list
	 *
	 * @author salvipascual
	 * @param String email
	 */
	public static function unsubscribeFromEmailList($email)
	{

		Connection::query("UPDATE person SET mail_list=0 WHERE email='$email'");
	}

	/**
	 * Parsing all line images encoded as base64
	 *
	 * @param string $html
	 * @param string $prefix
	 * @return array
	 */
	public static function getInlineImagesFromHTML(&$html, $prefix = 'cid:', $suffix = '.jpg')
	{
		$imageList = [];
		$tidy = new tidy();
		$body = $tidy->repairString($html, array('output-xhtml' => true,  'preserve-entities' => 1), 'utf8');

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$images = $doc->getElementsByTagName('img');
		 if ($images->length > 0) {
			foreach ($images as $image) {
				$src = $image->getAttribute('src');
				$id = "img".uniqid();

				// ex: src = data:image/png;base64,...
				$p = strpos($src, ';base64,');

				if ($p!==false)
				{
					$type = str_replace("data:", "", substr($src, 0, $p));
					$src = substr($src, $p + 8);
					$ext = str_replace('image/', '', $type);
					self::clearStr($ext);
					$filename =  $id.".".$ext;

					if ($image->hasAttribute("data-filename"))
					{
						$filename = $image->getAttribute("data-filename");
					}

					$filename = str_replace(['"','\\'], '', $filename);
					$imageList[$filename] = ["type" => $type, "content" => $src, "filename" => $filename];
					$image->setAttribute('src', $prefix.$filename);
				}
			}
		}

		$html = $doc->saveHTML();
		return $imageList;
	}

	/**
	 * Put images as encoded as base64 to html
	 *
	 * @param string $html
	 * @param array $imageList
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function putInlineImagesToHTML($html, $imageList, $prefix = 'cid:')
	{
		$tidy = new tidy();
		$body = $tidy->repairString($html, array('output-xhtml' => true, 'preserve-entities' => 1), 'utf8');

		$doc = new DOMDocument();
		@$doc->loadHTML($body);

		$images = $doc->getElementsByTagName('img');

		if ($images->length > 0) {
			foreach ($images as $image) {
				$src = $image->getAttribute('src');
				$src = substr($src, strlen($prefix));
				if (isset($imageList[$src]))
				{
					$image->setAttribute('src', 'data:' . $imageList[$src]['type'] . ';base64,' . $imageList[$src]['content']);
				}
			}
		}

		$html = $doc->saveHTML();

		$pbody = stripos($html, '<body>');
		if ($pbody !== false)
			$html = substr($html, $pbody + 6);

		$pbody = stripos($html, '</body');
		if ($pbody !== false)
			$html = substr($html, 0, $pbody);

		return $html;
	}

	/**
	 * Recursive rmdir
	 *
	 * @param string $path
	 */
	public static function rmdir($path){
		if (is_dir($path)) {
			$dir = scandir($path);
			foreach ( $dir as $d )
			{
				if ($d != "." && $d != "..") {
					if (is_dir("$path/$d"))
					{
						self::rmdir("$path/$d");
					}
					else
					{
						unlink("$path/$d");
					}
				}
			}
			rmdir($path);
		}
	}

	/**
	 * Get the completion percentage of a profile
	 *
	 * @REMOVE delete from the system and remove
	 * @author salvipascual
	 * @param String $email
	 *
	 * @return Number, percentage of completion
	 */
	public static function getProfileCompletion($email)
	{
		$profile = self::getPerson($email);
		return $profile->completion;
	}

	/**
	 * Get the country name based on a code
	 *
	 * @author salvipascual
	 * @param String $countryCode
	 * @param String $lang
	 * @return String
	 */
	function getCountryNameByCode($countryCode, $lang='es')
	{
		// always code in uppercase
		$countryCode = strtoupper($countryCode);

		// get the country
		$country = Connection::query("SELECT $lang FROM countries WHERE code = '$countryCode'");

		// return the country name or empty string
		return isset($country[0]->$lang) ? $country[0]->$lang : '';
	}

	/**
	 * Get the US state name based on the code
	 *
	 * @author salvipascual
	 * @param String $stateCode
	 * @return String
	 */
	function getStateNameByCode($stateCode)
	{
		$usstates = [
			"AL"=>"Alabama","AK"=>"Alaska","AZ"=>"Arizona","AR"=>"Arkansas","CA"=>"California","CO"=>"Colorado","CT"=>"Connecticut","DE"=>"Delaware","FL"=>"Florida","GA"=>"Georgia","HI"=>"Hawaii",
			"ID"=>"Idaho","IL"=>"Illinois","IN"=>"Indiana","IA"=>"Iowa","KS"=>"Kansas","KY"=>"Kentucky","LA"=>"Louisiana","ME"=>"Maine","MD"=>"Maryland","MA"=>"Massachusetts","MI"=>"Michigan",
			"MN"=>"Minnesota","MS"=>"Mississippi","MO"=>"Missouri","MT"=>"Montana","NE"=>"Nebraska","NV"=>"Nevada","NH"=>"New","NJ"=>"New","NM"=>"New","NY"=>"New","NC"=>"North",
			"ND"=>"North","OH"=>"Ohio","OK"=>"Oklahoma","OR"=>"Oregon","PA"=>"Pennsylvania","RI"=>"Rhode","SC"=>"South","SD"=>"South","TN"=>"Tennessee","TX"=>"Texas","UT"=>"Utah","VT"=>"Vermont",
			"VA"=>"Virginia","WA"=>"Washington","WV"=>"West","WI"=>"Wisconsin","W"=>"Wyoming"];
		$stateCode = strtoupper($stateCode);
		return isset($usstates[$stateCode]) ? $usstates[$stateCode] : "";
	}

	/**
	 * Clear double spaces and other stuffs from HTML content
	 *
	 * @param string $html
	 * @return mixed
	 */
	public static function clearHtml($html)
	{
		$html = str_replace('&nbsp;',' ',$html);

		do {
			$tmp = $html;
			$html = preg_replace('#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $html );
		} while ( $html !== $tmp );

		return $html;
	}

	/**
	 * Create an alert and notify the alert group
	 *
	 * @author salvipascual
	 * @param string $text
	 * @param string $severity NOTICE,WARNING,ERROR
	 * @return mixed
	 */
	public static function createAlert($text, $severity = "WARNING")
	{
		// create basic message
		$message = "$severity: $text";

		// get the tier from the configs file
		$di = FactoryDefault::getDefault();

		// save WARNINGS and ERRORS in the database
		if($severity != "NOTICE") {
			try {
				// cut text and remove SQL code
				$safeStr = Connection::escape($text, 254);

				// get the config
				$config = $di->get('config')['database_dev'];
				$host = $config['host'];
				$user = $config['user'];
				$pass = $config['password'];
				$name = $config['database'];

				// connect to the database and insert alert
				$db = new mysqli($host, $user, $pass, $name);
				$db->query("INSERT INTO alerts (`type`,`text`) VALUES ('$severity','$safeStr')");
				$db->close();
			}
			catch(Exception $e) {
				$message .= " [CreateAlert:Database] ".$e->getMessage().' '.$e->getFile().": ".$e->getLine();
			}
		}

		// send the alert by email
		if($severity == "ERROR") {
			try{
				$email = new Email();
				$email->subject = $message;
				$email->body = "<b>MESSAGE:</b> $message<br/><br/><b>DATE:</b> ".date('l jS \of F Y h:i:s A');
				$email->sendAlert();
			} catch(Exception $e) {
				$message .= " [CreateAlert:Email] ".$e->getMessage();
			}
		}

		// save error log
		error_log($message);
		return false;
	}

	/**
	 * Replace Spanish tildes by their unicode characters
	 *
	 * @author salvipascual
	 * @param String $text
	 * @return String
	 */
	public static function removeTildes($text)
	{
		$text = str_replace(array("á", "Á", "&aacute;", "&Aacute;"), "a", $text);
		$text = str_replace(array("é", "É", "&eacute;", "&Eacute;"), "e", $text);
		$text = str_replace(array("í", "Í", "&iacute;", "&Iacute;"), "i", $text);
		$text = str_replace(array("ó", "Ó", "&oacute;", "&Oacute;"), "o", $text);
		$text = str_replace(array("ú", "Ú", "&uacute;", "&Uacute;"), "u", $text);
		$text = str_replace(array("ñ", "Ñ", "&ntilde;", "&Ntilde;"), "n", $text);
		$text = str_replace("¡", "&iexcl;", $text);
		$text = str_replace("¿", "&iquest;", $text);

		return $text;
	}

	/**
	 * Erase SQL code from input text to avoid sql injections
	 *
	 * @author salvipascual
	 * @param String $text
	 * @return String
	 */
	public static function sanitize($text)
	{
		$text = str_ireplace('select ', '', $text);
		$text = str_ireplace('insert ', '', $text);
		$text = str_ireplace('update ', '', $text);
		$text = str_ireplace('drop ', '', $text);
		return $text;
	}

	/**
	 * Get external data for the app
	 *
	 * @author salvipascual
	 * @param stdClass $person
	 * @param stdClass $requestData
	 * @param Service $service
	 * @param Response $response
	 * @return array (Object, Attachments)
	 */
	public static function getAppData($person, $requestData, &$response){
		
		// create the response
		$responseData = new stdClass();
		$responseData->reload = $requestData->command=="reload";

		if($responseData->reload){
			$profile = Social::prepareUserProfile(clone $person);
			$responseData->profile_picture = basename($profile->picture_internal);
			// attach user picture if exist
			if($profile->picture_internal) $response->attachments[] = $profile->picture_internal;

			// add services to the response
			$services = Connection::query("
				SELECT name, description, category
				FROM service
				WHERE listed=1");
			
			$responseData->active_services = [];
			$wwwroot = FactoryDefault::getDefault()->get('path')['root'];
			foreach ($services as $s) {
				$icon = "$wwwroot/services/{$s->name}/{$s->name}.png";
				if(file_exists($icon)) $response->serviceIcons[] = $icon;
				else $icon = "";

				$serv = new stdClass();
				$serv->name = $s->name;
				$serv->description = $s->description;
				$serv->category = $s->category;
				$serv->icon = basename($icon);
				$responseData->active_services[] = $serv;
			}
		}

		$responseData->username = $person->username;
		$responseData->credit = number_format($person->credit, 2, '.', '');
		$responseData->profile_completion = Social::getProfileCompletion($person);
		$responseData->img_quality = $person->img_quality;

		// get unread notifications
		$responseData->notifications = Connection::query("
			SELECT `text`, `origin` AS service, `link`, `inserted_date` AS received
			FROM notifications
			WHERE id_person={$person->id}  AND `send` = 0
			ORDER BY inserted_date DESC");

		// mark notifications as send
		if($responseData->notifications) Connection::query("
			UPDATE notifications SET send=1
			WHERE id_person={$person->id}  AND `send` = 0");

		// get the latest versin from the config
		$lastAppVersion = FactoryDefault::getDefault()->get('config')['global']['appversion'];
		$responseData->latest_app_version = "$lastAppVersion";

		// get or create the user's token
		$responseData->token = $person->token;
		if(empty($responseData->token)) {
			$responseData->token = md5(time().rand());
			Connection::query("UPDATE person SET token='{$responseData->token}' WHERE email='$email'");
		}

		// add the response mailbox
		$inboxes = Connection::query("SELECT email FROM delivery_input WHERE environment='app' AND active=1 ORDER BY received ASC");
		$max90Percent = intval((count($inboxes)-1) * 0.9);
		$inbox = $inboxes[rand(0, $max90Percent)]->email; // pick an inbox btw the first 90%
		$inbox = substr_replace($inbox, ".", rand(1, strlen($inbox)-1), 0); // add a dot
		$responseData->mailbox = "$inbox@gmail.com";

		$responseData->cache = "$response->cache";
		$responseData->has_service_templates = $response->attachService;

		// convert to JSON
		return json_encode($responseData);
	}

	/**
	 * Create a Service object and execute the response
	 * 
	 * @author salvipascual
	 * @param Person $person
	 * @param Input $input
	 * @param String $environment: web/app
	 */
	public static function runService($person, Input $input, $environment)
	{
		// get the name of the service based on the subject line
		$pieces = explode(" ", $input->command);
		$serviceName = strtolower($pieces[0]);
		$subServiceName = isset($pieces[1]) ? strtolower($pieces[1]) : "";

		// create a new Request object
		$request = new Request();
		$request->person = clone $person;
		$request->input = $input;
		$request->environment = $environment;

		// create a new Response
		$response = new Response();
		$response->serviceName = $serviceName;

		// @TODO check if the service exist
		// else trow an exception and stop

		// include and create a new service object
		include_once Utils::getPathToService($serviceName) . "/service.php";
		$serviceObj = new Service();

		// run the service
		$func = "_$subServiceName";
		if(method_exists($serviceObj, $func)) $service->$func($request, $response);
		else $serviceObj->_main($request, $response);

		// return the service's response
		return $response;
	}

	/**
	 * Configures the jsons to be sent as a attached ZIP
	 *
	 * @author ricardo@apretaste.org
	 * @return String, path to the file created
	 */
	public function generateZipResponse()
	{
		// get a random name for the file and folder
		$zipFile = Utils::getTempDir() . substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";

		// create the zip file
		$zip = new ZipArchive;
		$zip->open($zipFile, ZipArchive::CREATE);

		//attach the response and the data, if reload, the response doesn't exists
		if(file_exists($this->responseFile)) $zip->addFile($this->responseFile, "response.json");
		$zip->addFile($this->dataFile, "data.json");

		// all files and files
		foreach ($this->images as $image) $zip->addFile($image, basename($image));
		foreach ($this->files as $attachment) $zip->addFile($attachment, basename($attachment));
		foreach ($this->serviceIcons as $icon) $zip->addFile($icon, "icons/".basename($icon));

		//attach the service files if nedded
		if($this->attachService) {
			$path = $this->service->pathToService;
			$name = $this->service->name;
			$tpl_dir = $path."/templates";
			$layout_dir = $path."/layout";
			$img_dir = $path."/images";
			$files = ['config.json','styles.css','scripts.js'];

			$templates = array_diff(scandir($tpl_dir), array('..', '.'));
			foreach($templates as $tpl){
				$file = $tpl_dir."/$tpl";
				$zip->addFile($file,"$name/templates/".basename($file));
			}

			if(file_exists($layout_dir)){
				$layouts = array_diff(scandir($layout_dir), array('..', '.'));
				foreach($layouts as $layout){
					$file = $layout_dir."/$layout";
					$zip->addFile($file,"$name/layouts/".basename($file));
				}
			}

			if(file_exists($img_dir)){
				$images = array_diff(scandir($img_dir), array('..', '.'));
				foreach($images as $img){
					$file = $img_dir."/$img";
					$zip->addFile($file,"$name/images/".basename($file));
				}
			}

			foreach($files as $f){
				$f = $path."/$f";
				if(file_exists($f)) $zip->addFile($f,"$name/".basename($f));
			}
		}

		// close the zip file
		$zip->close();

		// return the path to the file
		return $zipFile;
	}
}
