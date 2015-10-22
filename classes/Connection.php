<?php

class Connection {
	/**
	 * Query the database and returs an array of objects
	 *
	 * @author salvipascual
	 * @param String $sql, valid sql query
	 * @return Array, list of rows or NULL if it is not a select
	 */
	//@NOTE SQL injection attack to Core Database
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
}