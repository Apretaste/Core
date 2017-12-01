<?php

use G4\Crypto\Crypt;
use G4\Crypto\Adapter\OpenSSL;

class Utils
{
	/**
	 * Returns a valid Apretaste email to send an email
	 *
	 * @author salvipascual
	 * @param String $seed, text to create the email
	 * @return String, email address
	 */
	public function getValidEmailAddress($seed="")
	{
		// get the current environment
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$environment = $di->get('environment');

		// get a random mailbox
		$connection = new Connection();
		$node = $connection->query("
			SELECT email FROM delivery_input
			WHERE environment='$environment' AND active=1
			ORDER BY RAND() LIMIT 1");

		// return the default email
		if(empty($node)) return "apretaste@gmail.com";

		// add alias to the email
		$name = $node[0]->email;
		$seed = preg_replace("/[^a-zA-Z0-9]+/", '', $seed);
		if(empty($seed)) $seed = $this->randomSentence(1);
		return "$name+$seed@gmail.com";
	}

	/**
	 * Returns an email address to contact the customer support
	 *
	 * @author salvipascual
	 * @return String, email address
	 */
	public function getSupportEmailAddress()
	{
		// get a random support email
		$connection = new Connection();
		$support = $connection->query("
			SELECT email FROM delivery_input
			WHERE environment='support' AND active=1
			ORDER BY RAND() LIMIT 1");

		// alert if no support mailbox
		if(empty($support)) $this->createAlert("No support email in table delivery_input", "ERROR");
		else $support = $support[0]->email;

		// add alias to the email
		$seed = $this->randomSentence(1);
		return "$support+$seed@gmail.com";
	}

	/**
	 * Returns the personal mailbox for a user
	 *
	 * @author salvipascual
	 * @param String $email, user's email
	 * @return String, email address
	 */
	public function getUserPersonalAddress($email)
	{
		$person = $this->getPerson($email);

		if(empty($person)) return $this->getValidEmailAddress();
		else return "apretaste+{$person->username}@gmail.com";
	}

	/**
	 * Format a link to be an Apretaste mailto
	 *
	 * @author salvipascual
	 * @param String , name of the service
	 * @param String , name of the subservice, if needed
	 * @param String , pharse to search, if needed
	 * @param String , body of the email, if necessary
	 * @return String, link to add to the href section
	 */
	public function getLinkToService($service, $subservice=false, $parameter=false, $body=false)
	{
		$link = "mailto:".$this->getValidEmailAddress()."?subject=".strtoupper($service);
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
	public function serviceExist($name)
	{
		// if serviceName is an alias get the service name
		$db = new Connection();
		$r = $db->query("SELECT * FROM service_alias WHERE alias = '$name';");
		if (isset($r[0]->service)) $name = $r[0]->service;

		// check if service exist and return its name
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		if(file_exists("$wwwroot/services/$name/config.xml")) return $name;
		else return false;
	}

	/**
	 * Check if the Person exists in the database
	 *
	 * @author salvipascual
	 * @param String $email
	 * @return Boolean, true if Person exist
	 */
	public function personExist($email)
	{
		$connection = new Connection();
		$res = $connection->query("SELECT email FROM person WHERE LOWER(email)=LOWER('$email')");
		return count($res) > 0;
	}

	/**
	 * Get a person's profile
	 *
	 * @author salvipascual
	 * @return Array or false
	 */
	public function getPerson($email)
	{
		// get the person
		$connection = new Connection();
		$person = $connection->query("SELECT * FROM person WHERE email = '$email'");

		// return false if person cannot be found
		if (empty($person)) return false;
		else $person = $person[0];

		$social = new Social();
		$person = $social->prepareUserProfile($person);

		// return person
		return $person;
	}

	/**
	 * Create a unique username using the email
	 *
	 * @author salvipascual
	 * @version 3.0
	 * @param String $email
	 * @return String, username
	 */
	public function usernameFromEmail($email)
	{
		$username = strtolower(preg_replace('/[^A-Za-z]/', '', $email)); // remove special chars and caps
		$username = substr($username, 0, 5); // get the first 5 chars

		$connection = new Connection();
		$res = $connection->query("SELECT username as users FROM person WHERE username LIKE '$username%'");
		if(count($res) > 0) $username = $username . count($res); // add a number after if the username exist

		// ensure the username is unique
		$res = $connection->query("SELECT username FROM person WHERE username='$username'");
		if( ! empty($res))
		{
			$hash = md5(uniqid().$username.$email);
			$hash = strtolower(preg_replace('/[^A-Za-z]/', '', $hash)); // remove special chars and caps
			$username = substr($hash, 0, 6); // get the first 6 chars
		}

		return $username;
	}

	/**
	 * Get the email from an username
	 *
	 * @author salvipascual
	 * @param String $username
	 * @return String email or false
	 */
	public function getEmailFromUsername($username)
	{
		// remove the @ symbol
		$username = str_replace("@", "", $username);

		// get the email
		$connection = new Connection();
		$email = $connection->query("SELECT email FROM person WHERE username='$username'");

		// return the email or false if not found
		if(empty($email)) return false;
		else return $email[0]->email;
	}

	/**
	 * Get the username from an email
	 *
	 * @author salvipascual
	 * @param String $email
	 * @return String username or false
	 */
	public function getUsernameFromEmail($email)
	{
		// get the username
		$connection = new Connection();
		$username = $connection->query("SELECT username FROM person WHERE email='$email'");

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
	public function getPathToService($serviceName)
	{
		// get the path to service
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$path = "$wwwroot/services/$serviceName";

		// check if the path exist and return it
		if(file_exists($path)) return $path;
		else return false;
	}

	/**
	 * Return the current Raffle or false if no Raffle was found
	 *
	 * @author salvipascual
	 * @return Array or false
	 */
	public function getCurrentRaffle()
	{
		// get the raffle
		$connection = new Connection();
		$raffle = $connection->query("SELECT * FROM raffle WHERE CURRENT_TIMESTAMP BETWEEN start_date AND end_date");

		// return false if there is no open raffle
		if (count($raffle)==0) return false;
		else $raffle = $raffle[0];

		// get number of tickets opened
		$openedTickets = $connection->query("SELECT count(ticket_id) as opened_tickets FROM ticket WHERE raffle_id is NULL");
		$openedTickets = $openedTickets[0]->opened_tickets;

		// get the image of the raffle
		$di = \Phalcon\DI\FactoryDefault::getDefault();
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
	public function generateRandomHash()
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
	 * @param String $imagePath, path to the image
	 * @param number $width Fit to width
	 * @param number $height Fit to height
	 * @param number $quality Decrease/increase quality
	 * @param string $format Convert to format
	 * @return boolean
	 */
	public function optimizeImage($imagePath, $width = "", $height = "", $quality = 70, $format = 'image/jpeg')
	{
		// include SimpleImage class
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		include_once "$wwwroot/lib/SimpleImage.php";

		// optimize image
		try
		{
			$img = new \abeautifulsite\SimpleImage();
			$img->load($imagePath);
			if ( ! empty($width)) $img->fit_to_width($width);
			if ( ! empty($height)) $img->fit_to_height($height);
			$img->save($imagePath, $quality, $format);
		}
		catch (Exception $e) { return false; }
		return true;
	}

	/**
	 * Get the pieces of names from the full name
	 *
	 * @author hcarras
	 * @param String $name, full name
	 * @return Array [$firstName, $middleName, $lastName, $motherName]
	 */
	public function fullNameToNamePieces($name)
	{
		$namePieces = explode(" ", $name);
		$newNamePieces = array();
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
	 * Checks if an email can be delivered to a certain mailbox
	 *
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param Enum $direction, in or out, if we check an email received or sent
	 * @return String, ok,hard-bounce,soft-bounce,spam,no-reply,loop,failure,temporal,unknown
	 */
	public function deliveryStatus($email)
	{
		// check if we already have a status for the email
		$connection = new Connection();
		$res = $connection->query("SELECT status FROM delivery_checked WHERE email='$email'");
		if(empty($res)) {$status = ""; $code = "";} else return $res[0]->status;

		// block no reply emails
		if(empty($status) && (
			stripos($email,"not-reply")!==false ||
			stripos($email,"notreply")!==false ||
			stripos($email,"No_Reply")!==false ||
			stripos($email,"Do_Not_Reply")!==false ||
			stripos($email,"no-reply")!==false ||
			stripos($email,"noreply")!==false ||
			stripos($email,"no-responder")!==false ||
			stripos($email,"noresponder")!==false)
		) $status = 'no-reply';

		// block emails sending 30+ of the same request in 5 mins
		if(empty($status)) {
			$received = $connection->query("SELECT COUNT(id) as total FROM delivery WHERE user='$email' AND request_date > date_sub(now(), interval 5 minute)");
			if ($received[0]->total > 30) $status = 'loop';
		}

		// validate using external tools
		if(empty($status)) {
			// connect to email-validator.net
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			if($di->get('tier') == "sandbox") $code = "200";
			else {
				$key = $di->get('config')['emailvalidator']['key'];
				$r = json_decode(@file_get_contents("https://api.email-validator.net/api/verify?EmailAddress=$email&APIKey=$key"));
				if( ! $r) $this->createAlert("Error connecting to emailvalidator for $email", "ERROR");
				$code = $r->status;
			}

			// return the status based on the code
			$status = 'unknown'; // for non-recognized codes
			if(in_array($code, array("121","200","207","305","308","215","114"))) $status = 'ok';
			if(in_array($code, array("118","119","313","314"))) $status = 'temporal';
			if(in_array($code, array("413","406"))) $status = 'soft-bounce';
			if(in_array($code, array("302","314","317","401","404","410","414","420"))) $status = 'hard-bounce';
			if($code == "303") $status = 'spam';
			if($code == "409") $status = 'no-reply';
		}

		// save all emails tested so we dot duplicated the check
		$connection->query("INSERT INTO delivery_checked (email,status,code) VALUES ('$email','$status','$code')");
		return $status;
	}

	/**
	 * Return path to the temporal folder
	 *
	 * @author Kuma
	 * @return string
	 */
	public function getTempDir()
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		return "$wwwroot/temp/";
	}

	/**
	 * Return path to the public temp folder
	 *
	 * @author salvipascual
	 * @param Enum $path root|http
	 * @return string
	 */
	public function getPublicTempDir($path='root')
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
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
	public function detokenize($token)
	{
		// get the email for a token
		$connection = new Connection();
		$auth = $connection->query("SELECT id, email FROM authentication WHERE token='$token'");

		if(empty($auth)) return false;
		return $auth[0]->email;
	}

	/**
	 * Clear string
	 *
	 * @param String $name
	 * @return String
	 */
	public function clearStr($name, $extra_chars = '', $chars = "abcdefghijklmnopqrstuvwxyz")
	{
		$l = strlen($name);

		$newname = '';
		$chars .= $extra_chars;

		for ($i = 0; $i < $l; $i++)
		{
			$ch = $name[$i];
			if (stripos($chars, $ch) !== false)
				$newname .= $ch;
		}

		return $newname;
	}

	/**
	 * Recursive str replace
	 *
	 * @param mixed $search
	 * @param mixed $replace
	 * @param String $subject
	 */
	public function recursiveReplace($search, $replace, $subject)
	{
		$MAX = 1000;
		$i = 0;

		while (stripos($subject, $search))
		{
			$i++;

			$subject = str_ireplace($search, $replace, $subject);

			if ($i > $MAX)
				break;
		}

		return $subject;
	}

	/**
	 * Extract emails from text
	 *
	 * @param string $text
	 * @return mixed
	 */
	public function getEmailFromText($text)
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
	public function getCellFromText($text)
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
	public function getPhoneFromText($text)
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
	public function getFriendlySize($size)
	{
		$unit = array(
			'b',
			'kb',
			'mb',
			'gb',
			'tb',
			'pb'
		);
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}

	/**
	 * Convert date from spanish to mysql
	 *
	 * @param string $spanishDate
	 * @return string
	 */
	public function dateSpanishToMySQL($spanishDate)
	{
		$months = array(
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		);

		// separate each piece of the date
		$spanishDate = preg_replace("/\s+/", " ", $spanishDate);
		$spanishDate = str_replace(",", "", $spanishDate);
		$arrDate = explode(" ", $spanishDate);

		// create the standar, english date
		$month = array_search($arrDate[3], $months) + 1;
		$day = $arrDate[1];
		$year = $arrDate[5];
		$time = $arrDate[6] . " " . $arrDate[7];
		$date = "$month/$day/$year $time";

		// format and return date
		return date("Y-m-d H:i:s", strtotime($date));
	}

	/**
	 * Detect province from phone number
	 *
	 * @param string $phone
	 * @return string
	 */
	public function getProvinceFromPhone($phone)
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
	 * Get today date in spanish
	 *
	 * @return string
	 */
	public function getTodaysDateSpanishString()
	{
		$months = array(
			"Enero",
			"Febrero",
			"Marzo",
			"Abril",
			"Mayo",
			"Junio",
			"Julio",
			"Agosto",
			"Septiembre",
			"Octubre",
			"Noviembre",
			"Diciembre"
		);

		$today = explode(" ", date("j n Y"));
		return $today[0] . " de " . $months[$today[1] - 1] . " del " . $today[2];
	}

	/**
	 * Insert a notification in the database
	 *
	 * @author kuma
	 * @param string $email
	 * @param string $origin
	 * @param string $text
	 * @param string $link
	 * @param string $tag
	 * @return integer
	 */
	public function addNotification($email, $origin, $text, $link='', $tag='INFO')
	{
		// increase number of notifications
		$connection = new Connection();
		$connection->query("UPDATE person SET notifications = notifications+1 WHERE email = '$email'");

		// insert notification and return id
		return $connection->query("INSERT INTO notifications (email, origin, text, link, tag) VALUES ('$email','$origin','$text','$link','$tag')");
	}

	/**
	 * Return the number of notifications for a user
	 *
	 * @param string $email
	 * @return integer
	 */
	public function getNumberOfNotifications($email)
	{
		// temporal mechanism?
		$connection = new Connection();
		$r = $connection->query("SELECT notifications FROM person WHERE notifications is null AND email = '$email'");
		if ( ! isset($r[0]))
		{
			$r[0] = new stdClass();
			$r[0]->notifications = '';
		}

		$notifications = $r[0]->notifications;
		if (trim($notifications) == '')
		{
			// calculate notifications and update the number
			$r = $connection->query("SELECT count(id) as total FROM notifications WHERE email ='$email' AND viewed = 0;");
			$notifications = $r[0]->total * 1;
			$connection->query("UPDATE person SET notifications = $notifications WHERE email ='$email'");
		}

		return $notifications * 1;
	}

	/**
	 * Return user's notifications, and mark notifications as read
	 *
	 * @param string $email
	 * @return array
	 */
	public function getUnreadNotifications($email)
	{
		// get all user notifications
		$connection = new Connection();
		$notifications = $connection->query("SELECT * FROM notifications WHERE viewed='0' AND email='$email' ORDER BY inserted_date DESC");

		// Mark all notifications as read
		if($notifications){
//			$connection->query("UPDATE notifications SET viewed=1, viewed_date=CURRENT_TIMESTAMP WHERE email='$email'");
		}

		return $notifications;
	}

	/**
	 * Encript a message using the user's public key.
	 *
	 * @author salvipascual
	 * @param String $text
	 * @return String
	 */
	public function encrypt($text)
	{
		// get the seed from the config file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
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
	 * @param String $text
	 * @return String
	 */
	public function decrypt($text)
	{
		// get the seed from the config file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$seed = $di->get('config')['global']['seed'];

		// configure crypto with SSL
		$crypto = new Crypt(new OpenSSL());
		$crypto->setEncryptionKey($seed);

		// decript message
		return $crypto->decode($text);
	}

	/**
	 * Get a person's Nauta password
	 *
	 * @author salvipascual
	 * @param String $email
	 * @return String | false
	 */
	public function getNautaPassword($email)
	{
		// check if we have the nauta pass for the user
		$connection = new Connection();
		$pass = $connection->query("SELECT pass FROM authentication WHERE email='$email' AND appname='apretaste'");

		// return false if the password do not exist
		if(empty($pass)) return false;

		// else decript and return the password
		return $this->decrypt($pass[0]->pass);
	}

	/**
	 * Regenerate a sentense with random Spanish words
	 *
	 * @author salvipascual
	 * @param Integer $count, number of words selected
	 * @return String
	 */
	public function randomSentence($count=-1)
	{
		// get the number of words when no param passed
		if ($count == -1 || $count == 0) $count = rand(2, 10);

		// list of possible words to select
		$words = array("abajo","abandonar","abrir","abrir","absoluto","abuelo","acabar","acabar","acaso","accion","aceptar","aceptar","acercar","acompanar","acordar","actitud","actividad","acto","actual","actuar","acudir","acurdo","adelante","ademas","adquirir","advertir","afectar","afirmar","agua","ahora","aire","alcanzar","lcanzar","alejar","aleman","algo","alguien","alguno","algun","alla","alli","alma","alto","altura","amr","ambos","americano","amigo","amor","amplio","anadir","analisis","andar","animal","ante","anterior","antes","antiguo","anunciar","aparecer","aparecer","apenas","aplicar","apoyar","aprender","aprovechar","aquel","aquello","aqui","arbol","arma","arriba","arte","asegurar","asi","aspecto","asunto","atencio","atras","atreverse","aumentar","aunque","autentico","autor","autoridad","avanzar","ayer","ayuda","audar","ayudar","azul","bajar","bajo","barcelona","barrio","base","bastante","bastar","beber","bien","lanco","boca","brazo","buen","buscar","buscar","caballo","caber","cabeza","cabo","cada","cadena","cae","caer","calle","cama","cambiar","cambiar","cambio","caminar","camino","campana","campo","cantar","cntidad","capacidad","capaz","capital","cara","caracter","carne","carrera","carta","casa","casar","cas","caso","catalan","causa","celebrar","celula","central","centro","cerebro","cerrar","ciones","comenzr","como","comprender","conocer","conseguir","considerar","contar","convertir","correr","crear","cree","cumplir","deber","decir","dejar","descubrir","dirigir","empezar","encontrar","entender","entrar","scribir","escuchar","esperar","estar","estudiar","existir","explicar","formar","ganar","gustar","habe","hablar","hacer","intentar","jugar","leer","levantar","llamar","llegar","llevar","lograr","mana","mntener","mirar","nacer","necesitar","ocurrir","ofrecer","paces","pagar","parecer","partir","prtir","pasar","pedir","pensar","perder","permitir","plia","poder","poner","preguntar","presentar","prducir","quedar","querer","racteres","realizar","recibir","reconocer","recordar","resultar","saber","scar","salir","seguir","sentir","servir","suponer","tener","terminar","tocar","tomar","trabajar","trae","tratar","traves","utilizar","venir","vivir","volver");

		// get the sentence
		$sentence = array();
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
	public function getCampaignTracking($email)
	{
		// if it is not a campaign, return false
		if (strpos($email, '_t') == false) return false;

		// get the handle
		$res = explode("_t", $email);
		$res = explode("@", $res[1]);
		$handle = explode("@", $res[0]);

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
	public function subscribeToEmailList($email)
	{
		$connection = new Connection();
		$connection->query("UPDATE person SET mail_list=1 WHERE email='$email'");
	}

	/**
	 * Delete a subscriber from the email list
	 *
	 * @author salvipascual
	 * @param String email
	 */
	public function unsubscribeFromEmailList($email)
	{
		$connection = new Connection();
		$connection->query("UPDATE person SET mail_list=0 WHERE email='$email'");
	}

	/**
	 * Parsing all line images encoded as base64
	 *
	 * @param string $html
	 * @param string $prefix
	 * @return array
	 */
	public function getInlineImagesFromHTML(&$html, $prefix = 'cid:', $suffix = '.jpg')
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
					$this->clearStr($ext);
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
	 * @return array
	 */
	public function putInlineImagesToHTML($html, $imageList, $prefix = 'cid:')
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
	public function rmdir($path){
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
	 * @return Number, percentage of completion
	 */
	public function getProfileCompletion($email)
	{
		$profile = $this->getPerson($email);
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
		$connection = new Connection();
		$country = $connection->query("SELECT $lang FROM countries WHERE code = '$countryCode'");

		// return the country name or empty string
		return isset($country[0]->$lang) ? $country[0]->$lang : '';
	}

	/**
	 * Clear double spaces and other stuffs from HTML content
	 *
	 * @param string $html
	 * @return mixed
	 */
	public function clearHtml($html)
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
	 * @param String $text
	 * @param Enum $type: NOTICE,WARNING,ERROR
	 */
	public function createAlert($text, $severity="NOTICE")
	{
		// save alert into the database
		$connection = new Connection();
		$text = $connection->escape($text);
		$connection->query("INSERT INTO alerts (`type`,`text`) VALUES ('$severity','$text')");

		// send the alert to the error log
		$subject = "$severity: $text";
		error_log($subject);

		// get the tier from the configs file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$tier = $di->get('config')['global']['tier'];

		// if the email is an error
		if($severity == "ERROR" && $tier == "production")
		{
			// send the alert by email
			$email = new Email();
			$email->to = $di->get('config')['global']['alerts'];
			$email->subject = substr($subject, 0, 80);
			$email->body = "<b>SEVERITY:</b> $severity<br/><br/><b>TIER:</b> $tier<br/><br/><b>TEXT:</b> $text<br/><br/><b>DATE:</b> ".date('l jS \of F Y h:i:s A');
			$email->send();
		}

		return false;
	}

	/**
	 * Replace Spanish tildes by their unicode characters
	 *
	 * @author salvipascual
	 * @param String $text
	 * @return String
	 */
	public function removeTildes($text)
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
	 * Based on a subject process a request and return the Response
	 *
	 * @author salvipascual
	 * @param String $email
	 * @param String $subject
	 * @param String $body
	 * @param String[] $attachments
	 * @return [Service, Response[]]
	 */
	public function runRequest($email, $subject, $body, $attachments)
	{
		// sanitize subject and body to avoid mysql injections
		$subject = $this->sanitize($subject);
		$body = $this->sanitize($body);

		// get the name of the service or alias based on the subject line
		$subjectPieces = explode(" ", $subject);
		$serviceName = strtolower($subjectPieces[0]);
		unset($subjectPieces[0]);

		// get the service name, or use default service if the service does not exist
		$serviceName = $this->serviceExist($serviceName);
		if( ! $serviceName) $serviceName = "ayuda";

		// include the service code
		$pathToService = $this->getPathToService($serviceName);
		include_once "$pathToService/service.php";

		// get the subservice
		$subServiceName = "";
		if(isset($subjectPieces[1]) && ! preg_match('/\?|\(|\)|\\\|\/|\.|\$|\^|\{|\}|\||\!/', $subjectPieces[1])){
			$serviceClassMethods = get_class_methods($serviceName);
			$tempSubService = trim($subjectPieces[1]);
			if(preg_grep("/^_$tempSubService$/i", $serviceClassMethods)){
				$subServiceName = strtolower($tempSubService);
				unset($subjectPieces[1]);
			}
		}

		// get the language of the user
		$connection = new Connection();
		$result = $connection->query("SELECT username, lang FROM person WHERE email = '$email'");
		$lang = isset($result[0]->lang) ? $result[0]->lang : "es";
		$username = isset($result[0]->username) ? $result[0]->username : "";

		// create a new Request object
		$request = new Request();
		$request->email = $email;
		$request->username = $username;
		$request->subject = $subject;
		$request->body = $body;
		$request->attachments = $attachments;
		$request->service = $serviceName;
		$request->subservice = trim($subServiceName);
		$request->query = trim(implode(" ", $subjectPieces)); // get the service query
		$request->lang = $lang;

		// create a new Service Object with info from the database
		$result = $connection->query("SELECT * FROM service WHERE name = '$serviceName'");
		$service = new $serviceName();
		$service->serviceName = $serviceName;

		if (isset($result[0]))
		{
			$service->serviceDescription = $result[0]->description;
			$service->creatorEmail = $result[0]->creator_email;
			$service->serviceCategory = $result[0]->category;
			$service->serviceUsage = $result[0]->usage_text;
			$service->insertionDate = $result[0]->insertion_date;
			$service->showAds = $result[0]->ads == 1;
		} else {
			$service->serviceDescription = '';
			$service->creatorEmail = 'soporte@apretaste.com';
			$service->serviceCategory = 'service';
			$service->serviceUsage = '';
			$service->insertionDate = date('Y-m-d');
			$service->showAds = true;
		}

		$service->pathToService = $pathToService;
		$service->utils = $this;
		$service->request = $request;

		// run the service and get the Response
		$subserviceFunction = "_$subServiceName";

		if(empty($subServiceName) || ! method_exists($service, $subserviceFunction) ) $response = $service->_main($request);
		else $response = $service->$subserviceFunction($request);

		// get only the first response
		// @TODO remove when services send only one response
		if(is_array($response)) $response = $response[0];

		// create and return the response
		$return = new stdClass();
		$return->service = $service;
		$return->response = $response;
		return $return;
	}

	/**
	 * Erase SQL code from input text to avoid sql injections
	 *
	 * @author salvipascual
	 * @param String $text
	 * @return String
	 */
	public function sanitize($text)
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
	 * @param String $email
	 * @param String $timestamp
	 * @return Array(Object, Attachments)
	 */
	public function getExternalAppData($email, $timestamp)
	{
		// get the last update date
		$lastUpdateTime = empty($timestamp) ? 0 : $timestamp;
		$lastUpdateDate = date("Y-m-d H:i:s", $lastUpdateTime);

		// variable to store attach images
		$attachments = array();

		// get the person
		$connection = new Connection();
		$person = $connection->query("SELECT * FROM person WHERE email='$email'");

		// create the response
		$res = new stdClass();
		$res->timestamp = time();
		$res->username = $person[0]->username;
		$res->credit = number_format($person[0]->credit, 2, '.', '');

		// get the list of mailboxes
		$connection = new Connection();
		$inboxes = $connection->query("SELECT email FROM delivery_input WHERE environment='app' AND active=1 ORDER BY received ASC");

		// add the response mailbox
		$max90Percent = intval((count($inboxes)-1) * 0.9);
		$inbox = $inboxes[rand(0, $max90Percent)]->email; // pick an inbox btw the first 90%
		$inbox = substr_replace($inbox, ".", rand(1, strlen($inbox)-1), 0); // add a dot
		$res->mailbox = "$inbox+{$person[0]->username}@gmail.com";

		// check if there is any change in the profile
		$res->profile = new stdClass();
		if($lastUpdateTime < strtotime($person[0]->last_update_date))
		{
			// get the full profile
			$social = new Social();
			$person = $social->prepareUserProfile($person[0]);

			// add user profile to the response
			$res->profile->full_name = $person->full_name;
			$res->profile->date_of_birth = $person->date_of_birth;
			$res->profile->gender = $person->gender;
			$res->profile->phone = empty($person->cellphone) ? $person->phone : $person->cellphone;
			$res->profile->eyes = $person->eyes;
			$res->profile->skin = $person->skin;
			$res->profile->body_type = $person->body_type;
			$res->profile->hair = $person->hair;
			$res->profile->province = $person->province;
			$res->profile->city = $person->city;
			$res->profile->highest_school_level = $person->highest_school_level;
			$res->profile->occupation = $person->occupation;
			$res->profile->marital_status = $person->marital_status;
			$res->profile->interests = $person->interests;
			$res->profile->sexual_orientation = $person->sexual_orientation;
			$res->profile->religion = $person->religion;
			$res->profile->picture = basename($person->picture_internal);

			// attach user picture if exist
			if($person->picture_internal) $attachments[] = $person->picture_internal;
		}

		// get unread notifications
		$res->notifications = $connection->query("
			SELECT `text`, `origin` AS service, `link`, `inserted_date` AS received
			FROM notifications
			WHERE email='$email' AND viewed = 0
			ORDER BY inserted_date DESC");

		// mark notifications as read
		if($res->notifications) $connection->query("
			UPDATE notifications SET viewed=1, viewed_date=CURRENT_TIMESTAMP
			WHERE email='$email' AND viewed = 0");

		// get list of active services
		$res->active = array();
		$active = $connection->query("SELECT name FROM service WHERE listed=1");
		foreach ($active as $a) $res->active[] = $a->name;

		// get access to the configuration
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// get all services since last update
		$services = $connection->query("
			SELECT name, description, category, creator_email, insertion_date
			FROM service
			WHERE listed=1 AND insertion_date > '$lastUpdateDate'");

		// add services to the response
		$res->services = array();
		foreach ($services as $s) {
			// attach user picture if exist
			$icon = "$wwwroot/services/{$s->name}/{$s->name}.png";
			if(file_exists($icon)) $attachments[] = $icon;
			else $icon = "";

			$service = new stdClass();
			$service->name = $s->name;
			$service->description = $s->description;
			$service->category = $s->category;
			$service->creator = $s->creator_email;
			$service->updated = $s->insertion_date;
			$service->icon = basename($icon);
			$res->services[] = $service;
		}

		// get the latest versin from the config
		$appversion = $di->get('config')['global']['appversion'];
		$res->latest = "$appversion";

		// convert to JSON and return array
		return array(
			"attachments" => $attachments,
			"json" => json_encode($res));
	}
}
