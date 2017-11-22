<?php

class Security
{
	/**
	 * Login a user
	 *
	 * @author salvipascual
	 * @param String $emal
	 * @param String $pin
	 * @return Boolean
	 */
	public function login($email, $pin)
	{
		// check if the user/pin is ok
		$connection = new Connection();
		$person = $connection->query("SELECT first_name, picture FROM person WHERE email='$email' AND pin='$pin'");
		if(empty($person)) return false; else $person = $person[0];

		// get the path to root folder
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$httproot = $di->get('path')['http'];

		// get the profile image
		if($person->picture) $picture = "$httproot/profile/{$person->picture}.jpg";
		else $picture = $picture = "$httproot/images/user.jpg";

		// create the user object to save in the session
		$user = new stdClass();
		$user->email = $email;
		$user->name = $person->first_name;
		$user->picture = $picture;

		// add manager data if the user works at Apretaste
		$manager = $connection->query("SELECT occupation, pages, start_page FROM manage_users WHERE email='$email'");
		$user->isManager = ! empty($manager);
		$user->position = empty($manager) ? "" : $manager[0]->occupation;
		$user->pages = empty($manager) ? [] : explode(",", $manager[0]->pages);
		$user->startPage = empty($manager) ? "" : $manager[0]->start_page;

		// save the user in the session
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$di->getShared("session")->set("user", $user);
		return $user;
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
