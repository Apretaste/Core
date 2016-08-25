<?php

use Mailgun\Mailgun;
use abeautifulsite\SimpleImage;

class Utils
{
	/**
	 * Returns a valid Apretaste email to send an email
	 *
	 * @author salvipascual
	 * @return String, email address
	 */
	public function getValidEmailAddress()
	{
		$connection = new Connection();

		// get the email
		$sql = "SELECT email FROM jumper WHERE status='SendReceive' OR status='ReceiveOnly' ORDER BY last_usage ASC LIMIT 1";
		$result = $connection->deepQuery($sql);
		$email = $result[0]->email;

		// update the last time used
		$connection->deepQuery("UPDATE jumper SET last_usage=CURRENT_TIMESTAMP WHERE email='$email'");

		return $email;
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
	 * Check if the service exists
	 * 
	 * @author salvipascual
	 * @param String, name of the service
	 * @return Boolean, true if service exist
	 * */
	public function serviceExist(&$serviceName)
	{
		// check serviceName as an service alias
		$db = new Connection();
		
		// if serviceName is an alias and not is a name ...
		$r = $db->deepQuery("SELECT * FROM service_alias WHERE alias = '$serviceName';");
		
		// ... then get the service name
		if (isset($r[0]->service)) 
			$serviceName = $r[0]->service;
		
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		return file_exists("$wwwroot/services/$serviceName/config.xml");
	}

	/**
	 * Check if the Person exists in the database
	 * 
	 * @author salvipascual
	 * @param String $personEmail, email of the person
	 * @return Boolean, true if Person exist
	 * */
	public function personExist($personEmail)
	{
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT email FROM person WHERE LOWER(email)=LOWER('$personEmail')");
		return count($res) > 0;
	}

	/**
	 * Check if a person was invited by the same host and it is still pending 
	 *
	 * @author salvipascual
	 * @param String $host, Email of the person who is inviting
	 * @param String $guest, Email of the person invited
	 * @return Boolean, true if the invitation is pending
	 * */
	public function checkPendingInvitation($host, $guest)
	{
		$connection = new Connection();
		$res = $connection->deepQuery("SELECT id FROM invitations WHERE email_inviter='$host' AND email_invited='$guest' AND used=0");
		return count($res) > 0;
	}

	/**
	 * Get a person's profile
	 *
	 * @author salvipascual
	 * @return Array or false
	 * */
	public function getPerson($email)
	{
		// get the person
		$connection = new Connection();
		$person = $connection->deepQuery("SELECT * FROM person WHERE email = '$email'");

		// return false if there is no person with that email
		if (count($person)==0) return false;
		else $person = $person[0];

		// remove the pin from the response
		unset($person->pin);

		// get number of tickets for the raffle adquired by the user
		$tickets = $connection->deepQuery("SELECT count(*) as tickets FROM ticket WHERE raffle_id is NULL AND email = '$email'");
		$tickets = $tickets[0]->tickets;

		// get the person's full name
		$fullName = "{$person->first_name} {$person->middle_name} {$person->last_name} {$person->mother_name}";
		$fullName = trim(preg_replace("/\s+/", " ", $fullName));

		// get the image of the person
		$image = NULL;
		$thumbnail = NULL;
		if($person->picture)
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];

			if(file_exists("$wwwroot/public/profile/$email.jpg")) 
			{
				$image = "$wwwroot/public/profile/$email.jpg";
			}

			if(file_exists("$wwwroot/public/profile/thumbnail/$email.jpg"))
			{ 
				$thumbnail = "$wwwroot/public/profile/thumbnail/$email.jpg";
			}
		}

		// get the interests as an array
		$person->interests = preg_split('@,@', $person->interests, NULL, PREG_SPLIT_NO_EMPTY);

		// remove all whitespaces at the begining and ending
		foreach ($person as $key=>$value)
		{
			if( ! is_array($value)) $person->$key = trim($value); 
		}

		// add elements to the response
		$person->full_name = $fullName;
		$person->picture = $image;
		$person->thumbnail = $thumbnail;
		$person->raffle_tickets = $tickets;
		return $person;
	}

	/**
	 * Create a unique username using the email
	 *
	 * @author salvipascual
	 * @version 3.0
	 * @param String $email
	 * @return String, username
	 * */
	public function usernameFromEmail($email)
	{
		$connection = new Connection();
		$username = strtolower(preg_replace('/[^A-Za-z]/', '', $email)); // remove special chars and caps
		$username = substr($username, 0, 5); // get the first 5 chars
		$res = $connection->deepQuery("SELECT username as users FROM person WHERE username LIKE '$username%'");
		if(count($res) > 0) $username = $username . count($res); // add a number after if the username exist

		// ensure the username is in reality unique
		$res = $connection->deepQuery("SELECT username FROM person WHERE username='$username'");
		if( ! empty($res))
		{
			$hash = md5(uniqid().$username.$email);
			$hash = strtolower(preg_replace('/[^A-Za-z]/', '', $hash)); // remove special chars and caps
			$username = substr($hash, 0, 6); // get the first 6 chars
		}

		return $username;
	}

	/**
	 * Get the path to a service. 
	 * 
	 * @author salvipascual
	 * @param String $serviceName, name of the service to access
	 * @return String, path to the service, or false if the service do not exist
	 * */
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
	 * */
	public function getCurrentRaffle()
	{
		// get the raffle
		$connection = new Connection();
		$raffle = $connection->deepQuery("SELECT * FROM raffle WHERE CURRENT_TIMESTAMP BETWEEN start_date AND end_date");

		// return false if there is no open raffle
		if (count($raffle)==0) return false;
		else $raffle = $raffle[0];

		// get number of tickets opened
		$openedTickets = $connection->deepQuery("SELECT count(*) as opened_tickets FROM ticket WHERE raffle_id is NULL");
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
	 * @TODO Find an faster image optimization solution
	 * @author salvipascual
	 * @param String $imagePath, path to the image
	 * */
	public function optimizeImage($imagePath, $width = "", $height="", $quality = 70, $format = 'image/jpeg')
	{
		include "../lib/SimpleImage.php";
		
		$img = new SimpleImage();
		$img->load($imagePath);
		
		if ( ! empty($width))
			$img->fit_to_width($width);
		
		if ( ! empty($height))
			$img->fit_to_height($height);
		
		$img->save($imagePath, $quality, $format);
		
		/*
		if(empty($width) && empty($height)) $resize = "";
		else $resize = "-resize ".$width."x".$height;

		shell_exec("/usr/bin/convert $resize ".$imagePath."[0] ".$imagePath." > /var/www/Core/logs/convert.log");
		*/
	}

	/**
	 * Add a new subscriber to the email list in Mail Lite
	 * 
	 * @author salvipascual
	 * @param String email
	 * */
	public function subscribeToEmailList($email)
	{
		// never subscribe from the sandbox 
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') != "production") return;

		// get the path to the www folder
		$wwwroot = $di->get('path')['root'];

		// get the key from the config
		$mailerLiteKey = $di->get('config')['mailerlite']['key'];

		// adding the new subscriber to the list
		include_once "$wwwroot/lib/mailerlite-api-php-v1/ML_Subscribers.php";
		$ML_Subscribers = new ML_Subscribers($mailerLiteKey);
		$subscriber = array('email' => $email, 'resubscribe' => 1);
		$ML_Subscribers->setId("1266487")->add($subscriber);
	}

	/**
	 * Delete a subscriber from the email list in Mail Lite
	 * 
	 * @author salvipascual
	 * @param String email
	 * */
	public function unsubscribeFromEmailList($email)
	{
		// never unsubscribe from the sandbox
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		if($di->get('environment') != "production") return;

		// get the path to the www folder
		$wwwroot = $di->get('path')['root'];

		// get the key from the config
		$mailerLiteKey = $di->get('config')['mailerlite']['key'];

		// adding the new subscriber to the list
		include_once "$wwwroot/lib/mailerlite-api-php-v1/ML_Subscribers.php";
		$ML_Subscribers = new ML_Subscribers($mailerLiteKey);		
		$ML_Subscribers->setId("1266487")->remove($email);
	}

	/**
	 * Get the pieces of names from the full name
	 *
	 * @author hcarras
	 * @param String $name, full name
	 * @return Array [$firstName, $middleName, $lastName, $motherName]
	 * */
	public function fullNameToNamePieces($name)
	{
		$namePieces = explode(" ", $name);
		$newNamePieces = array();
		$tmp = "";

		foreach ($namePieces as $piece)
		{
			$tmp .= "$piece ";
		
			if(in_array(strtoupper($piece), array("DE","LA","Y","DEL")))
			{
				continue;
			}
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

		return array($firstName, $middleName, $lastName, $motherName);
	}

	/**
	 * Checks if an email can be delivered to a certain mailbox
	 *
	 * @author salvipascual
	 * @param String $to, email address of the receiver
	 * @param Enum $direction, in or out, if we check an email received or sent
	 * @return String, ok,hard-bounce,soft-bounce,spam,no-reply,loop,failure,temporal,unknown
	 * */
	public function deliveryStatus($to, $direction="out")
	{
		// variable to save the final response message
		$msg = "";

		// block people following the example email
		if(empty($msg) && $to == "su@amigo.cu") $msg = 'hard-bounce';

		// block email from/to our customer support
		if(empty($msg) && in_array($to, array("soporte@apretaste.com","comentarios@apretaste.com","contacto@apretaste.com","soporte@apretastes.com","comentarios@apretastes.com","contacto@apretastes.com","support@apretaste.zendesk.com" ,"support@apretaste.com","apretastesoporte@gmail.com"))) $msg = "loop";

		$connection = new Connection();
		
		// block address with same requested service in last hour
		// @TODO test and think in other requests/period frecuency like as 120/day (5 * 24 = 120)
		// @TODO save this $to address in a blacklist or penalize with 5 hours?
		$sql = "SELECT
		lower(substring_index(subject,' ',1)) as service,
		count(*) as total
		FROM delivery_received
		WHERE user = '$to'
		AND timediff(CURRENT_TIMESTAMP, inserted) <= '01:00:00'
		GROUP BY service
		HAVING total >= 5 AND (service = 'ayuda' OR NOT EXISTS (SELECT name FROM service WHERE service.name = service));";
		
		$lastreceived = $connection->deepQuery($sql);
		
		if (is_array($lastreceived)) if (isset($lastreceived[0])) $msg = 'loop';
		
		// block intents from blacklisted emails @TODO create a table for emails blacklisted
		//if(empty($msg) && stripos($to,"bachecubano.com")!==false) $msg = 'loop';

		// block intents to email the deamons
		//if(empty($msg) && (stripos($to,"mailer-daemon@")!==false || stripos($to,"communicationservice.nl")!==false )) $msg = 'hard-bounce';

		// check if the email is formatted properly
		if (empty($msg) && ! filter_var($to, FILTER_VALIDATE_EMAIL)) $msg = 'hard-bounce';

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

		// do not send any email that hardfailed before
		if(empty($msg))
		{
			$hardfail = $connection->deepQuery("SELECT COUNT(email) as hardfails FROM delivery_dropped WHERE reason='hardfail' AND email='$to'");
			if($hardfail[0]->hardfails > 0) $msg = 'hard-bounce';
		}

		// block any previouly dropped email that had already failed for 5 times
		if(empty($msg))
		{ 
			$fail = $connection->deepQuery("SELECT count(email) as fail FROM delivery_dropped WHERE reason <> 'dismissed' AND reason <> 'loop' AND reason <> 'spam' AND email='$to'");
			if($fail[0]->fail > 3) $msg = 'failure';
		}

		// block emails from apretaste to apretaste
		if(empty($msg))
		{
			$mailboxes = $connection->deepQuery("SELECT email FROM jumper");
			foreach($mailboxes as $m) if($to == $m->email) $msg = 'loop';
		}

		// check deeper for new people. Only check deeper the outgoing emails
		$code = "";
		if(empty($msg) && ! $this->personExist($to) && $direction=="out")
		{
			// use the cache if the email was checked before
			$cache = $connection->deepQuery("SELECT reason, code FROM delivery_checked WHERE email='$to' ORDER BY inserted DESC LIMIT 1");

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
			$connection->deepQuery("INSERT INTO delivery_dropped(email,reason,code,description) VALUES ('$to','$msg','$code','$direction')");
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
	 * */
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
		$connection->deepQuery("INSERT INTO delivery_checked (email,reason,code) VALUES ('$email','$reason','$code')");

		return array($reason, $code);
	}

	/**
	 * Get the completion percentage of a profile
	 *
	 * @author kuma, updated by salvipascual
	 * @param String, email of the person
	 * @return Number, percentage of completion
	 * */
	public function getProfileCompletion($email)
	{
		$profile = $this->getPerson($email);
		$percent = 0;

		if($profile)
		{
			$keys = get_object_vars($profile);
			$parts = 0;
			$total = count($keys);

			foreach($keys as $key=>$value)
			{
				// do not count non-required values
				if(
					$key == "middle_name" ||
					$key == "mother_name" ||
					$key == "about_me" ||
					$key == "updated_by_user" ||
					$key == "raffle_tickets" ||
					$key == "last_update_date" ||
					$key == "phone" ||
					$key == "cellphone" ||
					$key == "credit"
				) {$total--; continue;}

				// add non-empty values to the formula 
				if( ! empty($value)) $parts++;
			}

			// calculate percentage
			$percent = $parts / $total * 100;
		}
		return $percent;
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
	 * Authenticates a user and returns the token associated with the account
	 *
	 * @author salvipascual
	 * @param String email
	 * @param String pin
	 * @return String or false
	 */
	public function tokenize($email, $pin)
	{
		$connection = new Connection();

		// check if user/pass is correct
		$auth = $connection->deepQuery("SELECT email FROM person WHERE LOWER(email)=LOWER('$email') AND pin='$pin'");
		if(empty($auth)) return false;

		// get the new expiration date and token
		$expires = date("Y-m-d", strtotime("+3 days"));
		$token = md5($email.$pin.$expires.rand());

		// create new entry on the authentication table
		// and delete all previos entries for this token
		$connection->deepQuery("
			START TRANSACTION;
			DELETE FROM authentication WHERE email='$email';
			INSERT INTO authentication (token,email,expires) VALUES ('$token','$email','$expires');
			COMMIT");
		return $token;
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
		$connection = new Connection();

		// get the user if there is an active token
		$auth = $connection->deepQuery("SELECT * FROM authentication WHERE token='$token' AND CURRENT_TIMESTAMP < DATE(expires)");
		if(empty($auth)) return false;

		// extend the life of the token
		$expires = date("Y-m-d", strtotime("+3 days"));
		$connection->deepQuery("UPDATE authentication SET expires='$expires' WHERE id='{$auth[0]->id}'");

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
	 * Insert anotification in database
	 * 
	 * @author kuma
	 * @param string $email
	 * @param string $origin
	 * @param string $text
	 * @param string $link
	 * @param string $tag
	 * @return integer
	 */
	public function addNotification($email, $origin, $text, $link = '', $tag = 'INFO')
	{
		$sql = "INSERT INTO notifications (email, origin, text, link, tag) VALUES ('$email','$origin','$text','$link','$tag');";
		
		$connection = new Connection();
		$connection->deepQuery($sql);
		$r = $connection->deepQuery("SELECT LAST_INSERT_ID() as id;");
		
		if (isset($r[0]->id)) 
			return intval($r[0]->id);
		
		return false;
	}
	
	/**
	 * Return the number of notifications for a user
	 *
	 * @param string $email
	 * @return integer
	 */
	public function getNumberOfNotifications($email)
	{
		$connection = new Connection();
		$r = $connection->deepQuery("SELECT count(*) as total FROM notifications WHERE email ='{$email}' AND viewed = 0;");
		return $r[0]->total * 1;
	}
}
