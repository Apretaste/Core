<?php

class Connection
{
	static $__db = null;

	/**
	 * Singleton db connection
	 *
	 * @author kumahacker
	 *
	 * @return mixed|null
	 */
	static function db()
	{
		if(is_null(self::$__db))
		{
			$di         = \Phalcon\DI\FactoryDefault::getDefault();
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
	 *
	 * @param string $sql , valid sql query
	 *
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
				$rows   = [];
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
			$message  = $e->getMessage();
			$query    = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";
			$di       = \Phalcon\DI\FactoryDefault::getDefault();
			$www_root = $di->get('path')['root'];
			$logger   = new \Phalcon\Logger\Adapter\File("$www_root/logs/badqueries.log");
			$logger->log("$message\nQUERY: $query\n");
			$logger->close();

			throw $e;
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
	 *
	 * @author salvipascual
	 *
	 * @param String $str , text to scape
	 *
	 * @return String, escaped text ready to be sent to mysql
	 * */
	public function escape($str)
	{
		// get the escaped string
		$safeStr = self::db()->escapeString($str);

		// remove the ' at the beginning and end of the string
		return substr(substr($safeStr, 0, - 1), 1);
	}
}
