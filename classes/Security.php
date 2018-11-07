<?php

class Security
{
	/**
	 * Login a user
	 *
	 * @author salvipascual
	 * @param String $emal
	 * @param String $pin
	 * @return Object | Boolean
	 */
	public function login($email, $pin)
	{
		// check if the user/pin is ok
		$person = Connection::query("SELECT id, first_name, picture FROM person WHERE email='$email' AND pin='$pin'");
		if(empty($person)) return false; else $person = $person[0];

		// get the path to root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$httproot = $di->get('path')['http'];

		// get the profile image
		if($person->picture) $picture = "$httproot/profile/{$person->picture}.jpg";
		else $picture = $picture = "$httproot/images/user.jpg";

		// create the user object to save in the session
		$user = new stdClass();
		$user->id = $person->id;
		$user->email = $email;
		$user->name = $person->first_name;
		$user->picture = $picture;

		// add manager data if the user works at Apretaste
		$manager = Connection::query("SELECT occupation, pages, start_page FROM manage_users WHERE email='$email'");
		$user->isManager = ! empty($manager);
		$user->position = empty($manager) ? "" : $manager[0]->occupation;
		$user->pages = empty($manager) ? [] : explode(",", $manager[0]->pages);
		$user->startPage = empty($manager) ? "" : $manager[0]->start_page;

		// save the last user's IP and access time
		$ip = php::getClientIP();
		Connection::query("UPDATE person SET last_ip='$ip', last_access=CURRENT_TIMESTAMP WHERE id={$person->id}");

		// save the user in the session
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$di->getShared("session")->set("user", $user);
		return $user;
	}

	/**
	 * Login a user using the latest IP used
	 *
	 * @author salvipascual
	 * @param String $emal
	 * @return Object | Boolean
	 */
	public function loginByIP($email)
	{
		// get the lastest IP and date
		$person = Connection::query("SELECT last_ip, pin FROM person WHERE email='$email'");
		if(empty($person)) return false; else $person = $person[0];

		// check if user can be logged
		$ip = php::getClientIP();
		$localIP = $ip == "127.0.0.1";
		$sameIP = $ip == $person->last_ip;

		// log in the user and return
		if($localIP || $sameIP) return $this->login($email, $person->pin);
		else return false;
	}

	/**
	 * Login a user using a login hash
	 *
	 * @author salvipascual
	 * @param String $token
	 * @return Object | Boolean
	 */
	public function loginByToken($token)
	{
		// do not allow empty tokens
		if(empty($token)) return false;

		// get the email and pin using the token
		$person = Connection::query("SELECT email, pin FROM person WHERE token='$token'");
		if(empty($person)) return false;

		// log in the user and return
		return $this->login($person[0]->email, $person[0]->pin);
	}

	/**
	 * Close the current session
	 *
	 * @author salvipascual
	 */
	public function logout()
	{
		// get the group from the configs file
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$di->getShared("session")->remove("user");
		header("Location: /login"); exit;
	}

	/**
	 * Check if there is any user logged
	 *
	 * @author salvipascual
	 */
	public function checkLogin()
	{
		// check if the user is logged, else redirect
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		return $di->getShared("session")->has("user");
	}

	/**
	 * Check if the person logged has access to see a page
	 *
	 * @author salvipascual
	 */
	public function checkAccess($page)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$user = $di->getShared("session")->get("user");

		if(empty($user->pages)) return false;
		return (in_array("*", $user->pages) || in_array($page, $user->pages));
	}

	/**
	 * Check if there is any user logged and send to the login page
	 *
	 * @author salvipascual
	 */
	public function enforceLogin()
	{
		// check if the user is logged, else redirect
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$isUserLogin = $di->getShared("session")->has("user");

		// block when a user is not logged
		if( ! $isUserLogin) header("Location: /login");

		// check if the user has permissions
		$page = $di->get('router')->getControllerName();
		if( ! $this->checkAccess($page)) die("You have no access to this page");
	}

	/**
	 * Get the details for the user logged
	 *
	 * @author salvipascual
	 */
	public function getUser()
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		return $di->getShared("session")->get("user");
	}
}
