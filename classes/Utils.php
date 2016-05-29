<?php

use Mailgun\Mailgun;

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
	public function serviceExist($serviceName)
	{
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
	public function optimizeImage($imagePath, $width="", $height="")
	{
		if(empty($width) && empty($height)) $resize = "";
		else $resize = "-resize ".$width."x".$height;

		shell_exec("/usr/bin/convert $resize ".$imagePath."[0] ".$imagePath);
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

		// block intents from blacklisted emails @TODO create a table for emails blacklisted
		if(empty($msg) && stripos($to,"bachecubano.com")!==false) $msg = 'loop';

		// block intents to email the deamons
		if(empty($msg) && (stripos($to,"mailer-daemon@")!==false || stripos($to,"communicationservice.nl")!==false )) $msg = 'hard-bounce';

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

		$connection = new Connection();

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
}
