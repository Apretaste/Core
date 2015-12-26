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
	public function deepQuery($sql)
	{
		// get the database connection
		$di = \Phalcon\DI\FactoryDefault::getDefault();

		// only fetch for selects
		if(stripos($sql, "select") === 0)
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
			// execute statement in the database
			return $di->get('db')->execute($sql);
		}
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