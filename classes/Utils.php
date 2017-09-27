<?php

use Mailgun\Mailgun;
use abeautifulsite\SimpleImage;
use G4\Crypto\Crypt;
use G4\Crypto\Adapter\OpenSSL;
use Mremi\UrlShortener\Model\Link;
use Mremi\UrlShortener\Provider\Google\GoogleProvider;

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
		// get a random mailbox
		$connection = new Connection();
		$node = $connection->query("SELECT email FROM nodes_input WHERE active=1 ORDER BY RAND() LIMIT 1");
		$name = str_replace(".", "", explode("@", $node[0]->email)[0]);

		// add alias to the email
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
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$support = $di->get('config')['contact']['support'];

		// add alias to the email
		$parts = explode("@", $support);
		$seed = $this->randomSentence(1);
		$support = $parts[0]."+$seed@".$parts[1];

		return $support;
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
	 * Check if a person was invited by the same host and it is still pending
	 *
	 * @author salvipascual
	 * @param String $host, Email of the person who is inviting
	 * @param String $guest, Email of the person invited
	 * @return Boolean, true if the invitation is pending
	 */
	public function checkPendingInvitation($host, $guest)
	{
		$connection = new Connection();
		$res = $connection->query("SELECT id FROM invitations WHERE email_inviter='$host' AND email_invited='$guest' AND used=0");
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
			$img = new SimpleImage();
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
	public function deliveryStatus($to, $direction="out")
	{
		// never block emails from the team and specially the testers
		$connection = new Connection();
		$managers = $connection->query("SELECT email FROM manage_users");
		foreach ($managers as $manager) if($manager->email == $to) return "ok";

		// variable to save the final response message
		$msg = "";

		// block people following the example email
		if(empty($msg) && $to == "su@amigo.cu") $msg = 'hard-bounce';

		// check if the email is formatted properly
		if (empty($msg) && ! filter_var($to, FILTER_VALIDATE_EMAIL)) $msg = 'hard-bounce';

		// block email from/to our customer support
		if(empty($msg) && in_array($to, array("soporte@apretaste.com","comentarios@apretaste.com","contacto@apretaste.com","soporte@apretastes.com","comentarios@apretastes.com","contacto@apretastes.com","support@apretaste.zendesk.com" ,"support@apretaste.com","apretastesoporte@gmail.com"))) $msg = "loop";

		// block address with same requested service in last hour
		$lastreceived = $connection->query(
			"SELECT COUNT(id) as total
			FROM delivery_received
			WHERE user = '$to'
			AND TIME(timediff(CURRENT_TIMESTAMP, inserted)) <= TIME('00:30:00')");
		if (isset($lastreceived[0]->total) && $lastreceived[0]->total > 30) $msg = 'loop';

		// block intents from blacklisted emails @TODO create a table for emails blacklisted
		if(empty($msg) && stripos($to,"bachecubano.com")!==false) $msg = 'loop';

		// block no reply emails
		if(empty($msg) && (
			stripos($to,"not-reply")!==false ||
			stripos($to,"notreply")!==false ||
			stripos($to,"No_Reply")!==false ||
			stripos($to,"Do_Not_Reply")!==false ||
			stripos($to,"no-reply")!==false ||
			stripos($to,"noreply")!==false ||
			stripos($to,"no-responder")!==false ||
			stripos($to,"noresponder")!==false)
		) $msg = 'no-reply';

		// if the person received from Apretaste before, and he/she reaches again, unblock
		if(empty($msg) && $direction=="in")
		{
			$times = $connection->query("SELECT COUNT(id) as times FROM delivery_sent WHERE `user`='$to'");
			if($times[0]->times > 0)
			{
				$connection->query("DELETE FROM delivery_dropped WHERE email='$to'");
			}
		}

		// do not send any email that hardfailed before
		if(empty($msg))
		{
			$hardfail = $connection->query("SELECT COUNT(email) as hardfails FROM delivery_dropped WHERE reason='hardfail' AND email='$to'");
			if($hardfail[0]->hardfails > 0) $msg = 'hard-bounce';
		}

		// block any previouly dropped email that had already failed for 3 times
		if(empty($msg))
		{
			$fail = $connection->query("SELECT count(email) as fail FROM delivery_dropped WHERE reason <> 'loop' AND reason <> 'spam' AND email='$to'");
			if($fail[0]->fail > 3) $msg = 'failure';
		}

		// check deeper for new people. Only check deeper the outgoing emails
		$code = "";
		if(empty($msg) && ! $this->personExist($to) && $direction=="out")
		{
			// use the cache if the email was checked before
			$cache = $connection->query("SELECT reason, code FROM delivery_checked WHERE email='$to' ORDER BY inserted DESC LIMIT 1");

			// if the email hasen't been tested before or gave temporal errors
			if(empty($cache) || $cache[0]->reason == "temporal")
			{
				$return = $this->deepValidateEmail($to);
				$msg = $return[0];
				$code = $return[1];
			}
			else // for emails previously tested that failed, use the cache
			{
				$msg = $cache[0]->reason;
				$code = $cache[0]->code;
			}
		}

		// return if ok
		if (empty($msg) || $msg == "ok") return "ok";
		else
		{
			$connection->query("INSERT INTO delivery_dropped(email,reason,code,description) VALUES ('$to','$msg','$code','$direction')");
			return $msg;
		}
	}

	/**
	 * Validate an email to ensure we can send it to MailGun.
	 * We pay every email validated. Please use deliveryStatus()
	 * instead, unless you are re-validating an email previously sent.
	 *
	 * @author salvipascual
	 * @param Email $email
	 * @return Array [status, code]: ok,temporal,soft-bounce,hard-bounce,spam,no-reply,unknown
	 */
	public function deepValidateEmail($email)
	{
		// get validation key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$key = $di->get('config')['emailvalidator']['key'];

		$code = "200"; // code for the sandbox
		if($di->get('environment') != "sandbox")
		{
			// validate using email-validator.net
			$r = json_decode(@file_get_contents("https://api.email-validator.net/api/verify?EmailAddress=$email&APIKey=$key"));
			if( ! $r) throw new Exception("Error connecting with emailvalidator for user $email at ".date());

			$code = $r->status;
		}

		// return our table status based on the code
		$reason = 'unknown'; // for non-recognized codes
		if(in_array($code, array("121","200","207","305","308"))) $reason = 'ok';
		if(in_array($code, array("114","118","119","313","314","215"))) $reason = 'temporal';
		if(in_array($code, array("413","406"))) $reason = 'soft-bounce';
		if(in_array($code, array("302","314","317","401","404","410","414","420"))) $reason = 'hard-bounce';
		if($code == "303") $reason = 'spam';
		if($code == "409") $reason = 'no-reply';

		// save all emails tested so we dot duplicated the check
		$connection = new Connection();
		$connection->query("INSERT INTO delivery_checked (email,reason,code) VALUES ('$email','$reason','$code')");

		return array($reason, $code);
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
	 * Get differents statistics
	 *
	 * @author kuma
	 * @param string $stat_name
	 * @param array $params
	 * @return mixed
	 */
	public function getStat($statName = 'person.count', $params = array())
	{
		$sql = '';
		$connection = new Connection();

		// prepare query templates...
		$sqls = array(
			'person.count' => "SELECT count(email) as c FROM person;",
			'person.credit.max' => "SELECT max(credit) as c from person where email <> 'salvi.pascual@gmail.com' AND email not like '%@apretaste.com' and email not like 'apretaste@%';",
			'person.credit.min' => "SELECT min(credit) as c from person where email <> 'salvi.pascual@gmail.com' AND email not like '%@apretaste.com' and email not like 'apretaste@%' AND credit > 0;",
			'person.credit.avg' => "SELECT avg(credit) as c from person where email <> 'salvi.pascual@gmail.com' AND email not like '%@apretaste.com' and email not like 'apretaste@%';",
			'person.credit.sum' => "SELECT sum(credit) as c from person where email <> 'salvi.pascual@gmail.com' AND email not like '%@apretaste.com' and email not like 'apretaste@%';",
			'person.credit.count' => "SELECT count(email) as c FROM person where credit > 0;",
			'market.sells.monthly' => "SELECT count(subq.id) total, sum(credits) as pays, year(inserted_date) as y, month(inserted_date) as m from (select *, (select credits from _tienda_products where _tienda_orders.product = _tienda_products.code) as credits from _tienda_orders) as subq where datediff(current_timestamp,inserted_date) <= 365 group by y,m order by y,m;",
			'utilization.count' => "SELECT count(usage_id) FROM utilization;",
			'market.sells.byproduct.last30days' => "SELECT _tienda_products.name as name, count(_tienda_orders.id) as total FROM _tienda_orders INNER JOIN _tienda_products ON _tienda_products.code = _tienda_orders.product WHERE datediff(CURRENT_TIMESTAMP, _tienda_orders.inserted_date) <= 30 GROUP by name;"
		);

		if (!isset($sqls[$statName]))
			throw new Exception('Unknown stat '.$statName);

		$sql = $sqls[$statName];

		// replace params
		foreach ($params as $param => $value)
			$sql = str_replace($param, $value, $sql);

		// querying db ...
		$r = $connection->query($sql);

		if (!is_array($r))
			return null;

		// try return atomic result
		if (count($r) === 1)
			if (isset($r[0]))
			{
				$x = get_object_vars($r[0]);
				if (count($x) === 1)
					return array_pop($x);
			}

		// else return the entire array
		return $r;
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
	 * Return data of raffle's stars
	 *
	 * @author kuma
	 * @param $email string
	 * @param $from_today boolean
	 * @return integer
	 */
	public function getRaffleStarsOf($email, $from_today = true)
	{
		$connection = new Connection();
		$stars = 0;

		// last win
		$sql = "SELECT coalesce(datediff(current_date, max(event_date)), -1) as dt FROM events WHERE origin = 'stars-game' AND event_type = 'win-credit' AND email = '$email';";
		$r = $connection->query($sql);
		$dt = $r[0]->dt * 1;

		if ($dt == -1 || $dt > 5) $dt = 9999; // never win or long time ago

		// last usages
		$first = true;
		$sql = "";
		for ($d = 0; $d < 5; $d++)
		{
			if ($from_today === true || ($from_today === false && $d > 0)) // ignoring utilization of today or not
			{
				$sql .= ($first?"":" UNION ")."select current_date - $d, (select count(usage_id) from utilization WHERE requestor = '{$email}' AND service <> 'rememberme' and date(request_time) = current_date - $d) as uses";
				$first = false;
			}
		}
		$last_usage = $connection->query($sql);

		// count stars
		$d = $from_today ? 0 : 1;
		foreach ($last_usage as $lu)
		{
			// if use current day and not win...
			if ($lu->uses * 1 > 0 && $d < $dt) $stars++;
			else
				break; // not daily used
			$d++;
		}

		return $stars;
	}

	/**
	 * Get number of requests received from user today
	 *
	 * @author kuma
	 * @param $email
	 * @return mixed
	 */
	public function getTotalRequestsTodayOf($email)
	{
		$sql = "SELECT count(usage_id) as total FROM utilization
				WHERE date(request_time) = current_date
				and requestor = '$email'
				and service <> 'rememberme';";

		$connection = new Connection();
		$r = $connection->query($sql);

		return $r[0]->total * 1;
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

	public function addEvent($origin, $type, $email, $data)
	{
		$strData = serialize($data);
		$sql = "INSERT INTO events (origin, event_type, email, event_data) VALUES ('$origin', '$type', '$email', '$strData');";
		$connection = new Connection();
		$connection->query($sql);
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
	public function createAlert($text, $type="NOTICE")
	{
		// get the group from the configs file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$to = $di->get('config')['global']['alerts'];

		// get the details of the alert
		$date = date('l jS \of F Y h:i:s A');
		$subject = "$type: $text";
		$body = "SEVERITY: $type<br/>TEXT: $text<br/>DATE: $date";

		// save alert into the database
		$connection = new Connection();
		$text = str_replace("'", "", $text);
		$connection->query("INSERT INTO alerts (`type`,`text`) VALUES ('$type','$text')");

		// send the alert to the error log
		error_log($subject);

		// send email alert to the alerts group in case of errors
		if($text == "ERROR")
		{
			$email = new Email();
			$email->to = $to;
			$email->subject = $subject;
			$email->body = $body;
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
	 * Shorten an URL and return the new short URL
	 *
	 * @author salvipascual
	 * @param String $url
	 * @return String or false if error
	 */
	public function shortenUrl($url)
	{
		// get the Google API key
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$key = $di->get('config')['google']['key'];

		try{
			$link = new Link;
			$link->setLongUrl($url);
			$googleProvider = new GoogleProvider($key, array('connect_timeout'=>1, 'timeout'=>1));
			$shortenUrl = $googleProvider->shorten($link);
			return $link->getShortUrl();
		}catch (Exception $e){
			$this->createAlert("ERORR SHORTENING $url, ERROR:" . $e->getMessage(), "ERROR");
			return false;
		}
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
		$service->serviceDescription = $result[0]->description;
		$service->creatorEmail = $result[0]->creator_email;
		$service->serviceCategory = $result[0]->category;
		$service->serviceUsage = $result[0]->usage_text;
		$service->insertionDate = $result[0]->insertion_date;
		$service->pathToService = $pathToService;
		$service->showAds = $result[0]->ads == 1;
		$service->utils = $this;
		$service->request = $request;

		// run the service and get the Response
		$subserviceFunction = "_$subServiceName";

		if(empty($subServiceName) || ! method_exists($service, $subserviceFunction) ) $response = $service->_main($request);
		else $response = $service->$subserviceFunction($request);

		// make the responses to be always an array
		$responses = is_array($response) ? $response : array($response);

		// create and return the response
		$return = new stdClass();
		$return->service = $service;
		$return->responses = $responses;
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
}
