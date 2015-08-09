<?php 

class Service {
	public $serviceName;
	public $serviceDescription;
	public $creatorEmail;
	public $serviceCategory;
	public $serviceUsage;
	public $insertionDate;
	public $pathToService;
	public $utils; // Instance of the Utils class

	public function __construct() {
		$this->utils = new Utils($this->serviceName);
	}

	/**
	 * Query the database of the service and returs an array of objects
	 * 
	 * @author salvipascual
	 * @param String $sql, valid sql query
	 * @return Array, list of rows or NULL if it is not a select
	 */
	public function query($sql) {
		// @TODO implement this function
	}
}
