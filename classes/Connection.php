<?php

class Connection
{
	/**
	 * Query the database and returs an array of objects
	 *
	 * @author salvipascual
	 * @param String $sql, valid sql query
	 * @return Array, list of rows or NULL if it is not a select
	 */
	public function deepQuery($sql)
	{
		// query the database
		$di = \Phalcon\DI\FactoryDefault::getDefault();
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

	/**
	 * Query the database of service and returs an array of objects
	 *
	 * @author salvipascual
	 * @param String $sql, valid sql query
	 * @return Array, list of rows or NULL if it is not a select
	 */
	public function query($sql)
	{
		// @TODO
	}
}