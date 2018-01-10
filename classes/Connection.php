<?php

class Connection
{
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
		// get the database connection
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		try {
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				// query the database
				$result = $di->get('db')->query($sql);
				$result->setFetchMode(Phalcon\Db::FETCH_OBJ);

				// convert to array of objects
				$rows = [];
				while ($data = $result->fetch()) $rows[] = $data;
				return $rows;
			}
			else
			{
				// run query and return last insertd id
				$di->get('db')->execute($sql);
				return $di->get('db')->lastInsertId();
			}
		}
		catch(PDOException $e) // log the error and rethrow it
		{
			// create the message
			$query = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";
			$message = $e->getMessage() . "\nQUERY: $query\n";

			// save the bad queries log
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
		// get the escaped string
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$safeStr = $di->get('db')->escapeString($str);

		// remove the ' at the beginning and end of the string
		$safeStr = trim($safeStr, "'");

		// cut the string if a max number is passed
		if($cut) $safeStr = trim(substr($safeStr, 0, $cut));

		$safeStr = rtrim($safeStr, "\\");

		return $safeStr;
	}
}
