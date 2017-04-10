<?php

class Connection
{
	/**
	 * Query the database and returs an array of objects
	 * Please use escape() for all texts before creating the $sql
	 *
	 * @author salvipascual
	 * @param String $sql, valid sql query
	 * @return Array, list of rows or NULL if it is not a select
	 */
	public function query($sql)
	{
		// get the database connection
		$di = \Phalcon\DI\FactoryDefault::getDefault();

		try{
			// only fetch for selects
			if(stripos(trim($sql), "select") === 0)
			{
				// query the database
				$result = $di->get('db')->query($sql);
				$result->setFetchMode(Phalcon\Db::FETCH_OBJ);

				// convert to array of objects
				$rows = array();
				while ($data = $result->fetch())
				{
					$rows[] = $data;
				}
				// return the array of objects
				return $rows;
			}
			else
			{
				// run query and return last insertd id
				$di->get('db')->execute($sql);
				return $di->get('db')->lastInsertId();
			}
		}
		catch (PDOException $e) // log the error and rethrow it
		{
			$message = $e->getMessage();
			$query = isset($e->getTrace()[0]['args'][0]) ? $e->getTrace()[0]['args'][0] : "Query not available";

			$wwwroot = $di->get('path')['root'];
			$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/badqueries.log");
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
	 * @param String $str, text to scape
	 * @return String, scaped text ready to be sent to mysql
	 * */
	public function escape($str)
	{
		// get the scaped string
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$safeStr = $di->get('db')->escapeString($str);

		// remove the ' at the beginning and end of the string
		return substr(substr($safeStr, 0, -1), 1);
	}
}
