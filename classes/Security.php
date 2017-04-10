<?php

class Security
{
	/**
	 * Login a user
	 *
	 * @author salvipascual
	 * @param String $emal
	 * @param String $password
	 * @return Boolean
	 */
	public function login($email, $password)
	{
		// do not allow empty values
		if (empty($email) || empty($password)) return false;

		// try auth the user
		$password = sha1($password);
		$connection = new Connection();
		$res = $connection->query("SELECT * FROM manage_users WHERE email='$email' and password='$password'");

		// return false if combination not found
		if(empty($res)) return false;

		// create the manager object in session
		$manager = new stdClass();
		$manager->email = $res[0]->email;
		$manager->pages = explode(",", $res[0]->pages);
		$manager->group = $res[0]->group;
		$manager->startPage = $res[0]->start_page;

		// login the user in the session
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$di->getShared("session")->set("manager", $manager);

		// move to the user default init page
		header("Location: {$manager->startPage}");
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
		$di->getShared("session")->remove("manager");

		header("Location: /login");
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
		return $di->getShared("session")->has("manager");
	}

	/**
	 * Check if the person logged has access to see a page
	 *
	 * @author salvipascual
	 */
	public function checkAccess($page)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$manager = $di->getShared("session")->get("manager");
		if(empty($manager->pages)) return false;
		return (in_array("*", $manager->pages) || in_array($page, $manager->pages));
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
		$isUserLogin = $di->getShared("session")->has("manager");

		// block when a user is not logged
		if( ! $isUserLogin) header("Location: /login");

		// check if the user has permissions
		$page = $di->get('router')->getControllerName();
		if( ! $this->checkAccess($page)) die("You have no access to this page");
	}

	/**
	 * Get the details for the manager logged
	 *
	 * @author salvipascual
	 */
	public function getManager()
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		return $di->getShared("session")->get("manager");
	}
}
