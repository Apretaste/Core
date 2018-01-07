<?php

class Connection
{
	private static $db = false;

	/**
	 * Open a mysql connection or returns the active connection
	 *
	 * @author salvipascual
	 * @return Mysqli Resource
	 */
	public static function connect()
	{
		// return active connection if exist
		if(self::$db && self::$db->ping()) return self::$db;

		// get the conection params
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$config = $di->get('config');
		$host = $config['database']['host'];
		$username = $config['database']['user'];
		$password = $config['database']['password'];
		$dbname = $config['database']['database'];

		// connect to mysql and save new active connection
		self::$db = new mysqli($host, $username, $password, $dbname);
		if (self::$db->connect_error) error_log("[Connection] ".self::$db->connect_error);
		return self::$db;
	}

	/**
	 * Closes the active mysql connection
	 */
	public static function close()
	{
		if(self::$db && self::$db->ping()) self::$db->close();
	}

	/**
	 * Query the database and returs an array of objects
	 * Please use escape() for all texts before creating the $sql
	 *
	 * @author salvipascual
	 * @param string $sql, sql query
	 * @return Array/Integer, list of rows or LAST_ID if insert
	 */
	public static function query($sql)
	{
		self::connect();
		try {
			$result = self::$db->query($sql);

			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				$rows = [];
				while ($data = $result->fetch_object()) $rows[] = $data;
				return $rows;
			}
			else
			{
				// return last insertd id
				return self::$db->insert_id;
			}
		}
		catch(PDOException $e) // log the error and rethrow it
		{
			// create the message
			$query = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";
			$message = $e->getMessage() . "\nQUERY: $query\n";

			// save the bad queries log
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/badqueries.log");
			$logger->log($message);
			$logger->close();

			// create the alert
			$utils = new Utils();
			return $utils->createAlert($message, "ERROR");
		}
	}

	/**
	 * ALIAS of $this->query() for backward compativility
	 * @author salvipascual
	 */
	public static function deepQuery($sql)
	{
		return self::query($sql);
	}

	/**
	 * Escape dangerous strings before passing it to mysql
	 * @author salvipascual
	 * @param String $str, text to scape
	 * @param Intener $cut, number of chars to truncate the string
	 * @return String, escaped text ready to be sent to mysql
	 */
	public static function escape($str, $cut=false)
	{
		// escape the string
		self::connect();
		$safeStr = self::$db->real_escape_string($str);

		// cut the string if a max number is passed
		if($cut) $safeStr = trim(substr($safeStr, 0, $cut));

		return $safeStr;
	}
}
