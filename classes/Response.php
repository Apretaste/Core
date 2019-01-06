<?php

class Response
{
	public $serviceName;
	public $layout;
	public $template;
	public $json;
	public $images = [];
	public $files = [];
	public $render = false; // false if the response should not be sent to the user
	public $cache = 0;

	/**
	 * Set the cache in minutes. If no time is passed, default will be 1 year
	 *
	 * @author salvipascual
	 * @param String $cache | year, month, day, number of hours
	 */
	public function setCache($cache = "year")
	{
		if($cache == "year") $cache = 512640;
		if($cache == "month") $cache = 43200;
		if($cache == "week") $cache = 10080;
		if($cache == "day") $cache = 1440;
		$this->cache = $cache;
	}

	/**
	 * Set the layout of the response
	 *
	 * @author salvipascual
	 * @param String $layout, empty to set default layout
	 */
	public function setLayout($layout)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// check if a public layout is passed
		$file = "$wwwroot/app/layouts/$layout";
		if(file_exists($file)) $this->layout = $file;

		// else, check if is a service layout
		else {
			$file = Utils::getPathToService($this->serviceName) . "/layouts/$layout";
			if(file_exists($file)) $this->layout = $file;
		}
	}

	/**
	 * Build an HTML template response based on a text passed by the user
	 *
	 * @author salvipascual
	 * @param String, $text
	 */
	public function createFromText($text)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$this->template = "$wwwroot/app/templates/message.tpl";
		$this->json = json_encode(["text"=>$text]);
		$this->render = true;
		return $this;
	}

	/**
	 * Build an HTML template from a set of variables and a template name passed by the user
	 *
	 * @author salvipascual
	 * @param String $template, name of the file in the template folder
	 * @param String[] $content, in the way ["key"=>"var"]
	 * @param String[] $images, paths to the images to embeb
	 * @param String[] $files, paths to the files to attach
	 */
	public function createFromTemplate($template, $content, $images=[], $files=[])
	{
		$this->template = Utils::getPathToService($this->serviceName)."/templates/".$template;
		$this->json = json_encode($content);
		$this->images = $images;
		$this->files = $files;
		$this->render = true;
		return $this;
	}
}
