<?php

class Connection
{
	static $__db = null;

	/**
	 * Singleton db connection
	 *
	 * @param bool $force Force the connection
	 * @throws \Exception
	 * @author kumahacker
	 * @return mysqli
	 */
	static function db($force = false)
	{
		if(is_null(self::$__db) || $force)
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$config = $di->get('config');
			self::$__db = new mysqli($config['database']['host'],  $config['database']['user'], $config['database']['password'], $config['database']['database']);

			if (self::$__db ->connect_errno) {
				throw new Exception("Failed to connect to MySQL: (" . self::$__db ->connect_errno . ") " . self::$__db ->connect_error);
			}
		}

		return self::$__db;
	}

	/**
	 * Query the database and returs an array of objects
	 * Please use escape() for all texts before creating the $sql
	 *
	 * @author salvipascual
	 * @author kumahacker
	 *
	 * @throws Exception
	 *
	 * @param string $sql , valid sql query
	 *
	 * @return mixed, list of rows or NULL if it is not a select
	 */
	public function query($sql)
	{
		try
		{
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				// query the database
				if ($result = self::db()->query($sql))
				{
					// convert to array of objects
					$rows = [];
					while($data = $result->fetch_object()) $rows[] = $data;
					return $rows;
				}

				throw new Exception('MYSQL ERRROR #'.$this->db()->errno.', MSG: '.$this->db()->error.', SQLSTATE: '. $this->db()->sqlstate);
			}
			else
			{
				// run query and return last inserted id
				$stmt = self::db()->prepare($sql);
				$stmt->execute();

				return self::db()->insert_id;
			}
		}
		catch(PDOException $e) // log the error and rethrow it
		{
			// create the message
			$query = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";
			$message = $e->getMessage() . "\nQUERY: $query\n";

			// get the path to root
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];

			// save the bad queries log
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/badqueries.log");
			$logger->log($message);
			$logger->close();

			// hack to re-connect if connection is lost
			$goneAway = php::exists($message, "gone away");
			if($goneAway) self::db(true);

			// create the alert
			$utils = new Utils();
			return $utils->createAlert($message, "ERROR");
		}
	}

	/**
	 * ALIAS of $this->query() for backward compativility
	 * @author salvipascual
	 */
	public function deepQuery($sql)
	{
		return $this->query($sql);
	}

	/**
	 * Escape dangerous strings before passing it to mysql
	 * @author salvipascual
	 * @param String $str, text to scape
	 * @param Intener $cut, number of chars to truncate the string
	 * @return String, escaped text ready to be sent to mysql
	 */
	public function escape($str, $cut=false)
	{
		// get the escaped string
		$safeStr = self::db()->escape_string($str);

		// remove the ' at the beginning and end of the string
		$safeStr = substr(substr($safeStr, 0, - 1), 1);

		// cut the string if a max number is passed
		if($cut) $safeStr = trim(substr($safeStr, 0, $cut));

		return $safeStr;
	}

	/**
	 * Closes the connection to MySQL
	 * @author salvipascual
	 */
	public static function disconnect()
	{
		self::db()->close();
	}
}
