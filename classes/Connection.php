<?php

use \Phalcon\DI;

class Connection
{
	private static $db = null;

	/**
	 * Creates a new connection
	 * 
	 * @author salvipascual
	 * @param Boolean $write
	 * @return mysqli object
	 */
	public static function connect($write=false)
	{
		// get the host count
		$config = Di::getDefault()->get('config');
		$count = $config['database']['host_count'];

		// select host to use
		$stream = $write ? 1 : rand(1, $count);

		// get the config for the host
		$host = $config['database']["host_$stream"];
		$user = $config['database']['user'];
		$pass = $config['database']['password'];
		$name = $config['database']['database'];

		// connect to the database
		self::$db = new mysqli($host, $user, $pass, $name);
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
		try {
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0) {
				// connect to a reader stream
				$db = self::connect();

				// query the database
				$result = $db->query($sql);

				// convert to array of objects
				$rows = [];
				while ($data = $result->fetch_object()) $rows[] = $data;
				return $rows;
			}
			// run query and return last insertd id
			else {
				// connect to the writer stream
				$db = self::connect(true);

				// query the database
				$db->multi_query($sql);
				while ($db->next_result());
				return $db->insert_id;
			}
		}
		// log the error and rethrow it
		catch(mysqli_sql_exception $e) {
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
		if($cut) $safeStr = trim(mb_strcut($safeStr, 0, $cut,mb_detect_encoding($cut)));
		return rtrim($safeStr, "\\");
	}

	/**
	 * Close the connection
	 *
	 * @author salvipascual
	 */
	public static function close()
	{
		if( ! is_null(self::$db)) {
			self::$db->close();
			self::$db = null;
		}
	}
}