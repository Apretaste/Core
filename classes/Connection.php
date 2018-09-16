<?php

use \Phalcon\DI;

class Connection
{
	private static $db = null;

	/**
	 * Creates a new connection
	 * 
	 * @author salvipascual
	 * @return mysqli object 
	 */
	public static function connect()
	{
		if(is_null(self::$db) || ! self::$db->ping())
		{
			// get the config
			$config = Di::getDefault()->get('config');
			$host = $config['database']['host'];
			$user = $config['database']['user'];
			$pass = $config['database']['password'];
			$name = $config['database']['database'];

			// connect to the database
			self::$db = new mysqli($host, $user, $pass, $name);
		}
		
		return self::$db;
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
		// ensure we have a connection
		$db = self::connect();

		try {
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				// query the database
				$result = $db->query($sql);
				// convert to array of objects
				$rows = [];
				while ($data = $result->fetch_object()) $rows[] = $data;
				return $rows;
			}
			// run query and return last insertd id
			else
			{
				$db->multi_query($sql);
				while ($db->next_result());
				return $db->insert_id;
			}
		}
		// log the error and rethrow it
		catch(mysqli_sql_exception $e)
		{
			// create the message
			$query = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";
			$message = $e->getMessage() . "\nQUERY: $query\n";

			// save the bad queries log
			$wwwroot = Di::getDefault()->get('path')['root'];
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/badqueries.log");
			$logger->log($message);
			$logger->close();

			// create the alert
			return Utils::createAlert($message, "ERROR");
		}
	}

	/**
	 * ALIAS of $this->query() for backward compativility
	 *
	 * @author salvipascual
	 */
	public static function deepQuery($sql)
	{
		return self::query($sql);
	}

	/**
	 * Escape dangerous strings before passing it to mysql
	 *
	 * @author salvipascual
	 * @param String $str, text to scape
	 * @param Intener $cut, number of chars to truncate the string
	 * @return String, escaped text ready to be sent to mysql
	 */
	public static function escape($str, $cut=false)
	{
		// ensure we have a connection
		$db = self::connect();

		// get the escaped string
		$safeStr = $db->real_escape_string($str);

		// remove the ' at the beginning and end of the string
		$safeStr = trim($safeStr, "'");

		// cut the string if a max number is passed
		if($cut) $safeStr = trim(substr($safeStr, 0, $cut));
		return rtrim($safeStr, "\\");
	}

	/**
	 * Close the connection
	 *
	 * @author salvipascual
	 */
	public static function close(){
		if( ! is_null(self::$db) && self::$db->ping()) {
			self::$db->close();
		}
	}
}