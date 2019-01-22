<?php

use \Phalcon\DI\FactoryDefault;

class Security
{
	/**
	 * Login a user
	 *
	 * @author salvipascual
	 * @param String $email
	 * @return Object | Boolean
	 */
	public static function login($person){
		// get the path to root folder
		$httproot = FactoryDefault::getDefault()->get('path')['http'];

		// get the profile image
		if($person->picture) $picture = "$httproot/profile/{$person->picture}.jpg";
		else $picture = $picture = "$httproot/images/user.jpg";

		// create the user object to save in the session
		$user = new stdClass();
		$user->id = $person->id;
		$user->email = $person->email;
		$user->name = $person->first_name;
		$user->picture = $picture;

		// add manager data if the user works at Apretaste
		$manager = Connection::query("SELECT occupation, pages, start_page FROM manage_users WHERE email='$person->email'");
		$user->isManager = ! empty($manager);
		$user->position = empty($manager) ? "" : $manager[0]->occupation;
		$user->pages = empty($manager) ? [] : explode(",", $manager[0]->pages);
		$user->startPage = empty($manager) ? "" : $manager[0]->start_page;

		// save the last user's IP and access time
		$ip = php::getClientIP();
		Connection::query("UPDATE person SET last_ip='$ip', last_access=CURRENT_TIMESTAMP WHERE id={$person->id}");

		// save the user in the session
		FactoryDefault::getDefault()->getShared("session")->set("user", $user);
		return $user;
	}

	/**
	 * Login a user using the latest IP used
	 *
	 * @author salvipascual
	 * @param String $emal
	 * @return Boolean
	 */
	public static function loginByIP($person){
		// check if user can be logged
		$ip = php::getClientIP();
		$localIP = $ip == "127.0.0.1";
		$sameIP = $ip == $person->last_ip;

		// log in the user and return
		if($localIP || $sameIP) return self::login($person);
		else return false;
	}

	/**
	 * Login a user using a login hash
	 *
	 * @author salvipascual
	 * @param String $token
	 * @return Object | Boolean
	 */
	public static function loginByToken($token)
	{
		// do not allow empty tokens
		if(empty($token)) return '{"code":"300", "message":"Empty token"}';

		// get the email and pin using the token
		$person = Connection::query("SELECT id, email, first_name, pin, blocked, picture FROM person WHERE token='$token'");
		if(empty($person)) return '{"code":"301", "message":"Bad token"}';
		else $person = $person[0];

		if($person->blocked) return '{"code":"302", "message":"User is blocked"}';
		else if(!Utils::isAllowedDomain($person->email)) return '{"code":"303", "message":"Domain of email is not allowed"}';

		// log in the user and return
		return self::login($person);
	}

	/**
	 * Close the current session
	 *
	 * @author salvipascual
	 */
	public static function logout()
	{
		// get the group from the configs file
		$di = FactoryDefault::getDefault();
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
		$di = FactoryDefault::getDefault();
		return $di->getShared("session")->has("user");
	}

	/**
	 * Check if the person logged has access to see a page
	 *
	 * @author salvipascual
	 */
	public function checkAccess($page)
	{
		$di = FactoryDefault::getDefault();
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
		$di = FactoryDefault::getDefault();
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
	public function getUser(){
		$di = FactoryDefault::getDefault();
		return $di->getShared("session")->get("user");
	}
}
