<?php

class Connection
{
	static $__db = null;

	/**
	 * Singleton db connection
	 *
	 * @author kumahacker
	 * @return mixed|null
	 */
	static function db()
	{
		if(is_null(self::$__db))
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			self::$__db = $di->get('db');
			self::$__db->connect();
		}

		return self::$__db;
	}

	/**
	 * Query the database and returs an array of objects
	 * Please use escape() for all texts before creating the $sql
	 *
	 * @author salvipascual
	 * @param string $sql , valid sql query
	 * @return array, list of rows or NULL if it is not a select
	 */
	public function query($sql)
	{
		try
		{
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				// query the database
				$rows = [];
				$result = self::db()->query($sql);
				$result->setFetchMode(Phalcon\Db::FETCH_OBJ);

				// convert to array of objects
				while($data = $result->fetch()) $rows[] = $data;

				// return the array of objects
				return $rows;
			}
			else
			{
				// run query and return last inserted id
				self::db()->execute($sql);
				return self::db()->lastInsertId();
			}
		} catch(PDOException $e) // log the error and rethrow it
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
		$safeStr = self::db()->escapeString($str);

		// remove the ' at the beginning and end of the string
		$safeStr = substr(substr($safeStr, 0, - 1), 1);

		// cut the string if a max number is passed
		if($cut) $safeStr = trim(substr($safeStr, 0, $cut));

		return $safeStr;
	}
}
