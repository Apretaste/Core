<?php

class Service
{
	public $serviceName;
	public $serviceDescription;
	public $creatorEmail;
	public $serviceCategory;
	public $serviceUsage;
	public $insertionDate;
	public $pathToService;
	public $showAds;
	public $utils; // Instance of the Utils class

	public function __construct($serviceName = null)
	{
		if ( ! is_null($serviceName)) $this->serviceName = $serviceName;
		$this->utils = new Utils($this->serviceName);
	}
}
