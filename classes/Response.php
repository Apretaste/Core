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

	public $dataFile;
	public $attachService = false;
	public $serviceIcons = [];
	public $responseFile;

	/**
	 * Set the cache in minutes. If no time is passed, default will be 1 year
	 *
	 * @author salvipascual
	 * @param String $cache | year, month, day, number of hours
	 */
	public function setCache($cache = "year"){
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

	/**
	 * Configures the jsons to be sent as a attached ZIP
	 *
	 * @author ricardo@apretaste.org
	 * @return String, path to the file created
	 */
	public function generateZipResponse()
	{
		// get a random name for the file and folder
		$zipFile = Utils::getTempDir() . substr(md5(rand() . date('dHhms')), 0, 8) . ".zip";

		// create the zip file
		$zip = new ZipArchive;
		$zip->open($zipFile, ZipArchive::CREATE);

		//attach the response and the data, if reload, the response doesn't exists
		if(file_exists($this->responseFile)) $zip->addFile($this->responseFile, "response.json");
		$zip->addFile($this->dataFile, "data.json");

		// all files and files
		foreach ($this->images as $image) $zip->addFile($image, basename($image));
		foreach ($this->files as $attachment) $zip->addFile($attachment, basename($attachment));
		foreach ($this->serviceIcons as $icon) $zip->addFile($icon, "icons/".basename($icon));

		//attach the service files if nedded
		if($this->attachService) {
			$path = $this->service->pathToService;
			$name = $this->service->name;
			$tpl_dir = $path."/templates";
			$layout_dir = $path."/layout";
			$img_dir = $path."/images";
			$files = ['config.json','styles.css','scripts.js'];

			$templates = array_diff(scandir($tpl_dir), array('..', '.'));
			foreach($templates as $tpl){
				$file = $tpl_dir."/$tpl";
				$zip->addFile($file,"$name/templates/".basename($file));
			}

			if(file_exists($layout_dir)){
				$layouts = array_diff(scandir($layout_dir), array('..', '.'));
				foreach($layouts as $layout){
					$file = $layout_dir."/$layout";
					$zip->addFile($file,"$name/layouts/".basename($file));
				}
			}

			if(file_exists($img_dir)){
				$images = array_diff(scandir($img_dir), array('..', '.'));
				foreach($images as $img){
					$file = $img_dir."/$img";
					$zip->addFile($file,"$name/images/".basename($file));
				}
			}

			foreach($files as $f){
				$f = $path."/$f";
				if(file_exists($f)) $zip->addFile($f,"$name/".basename($f));
			}
		}

		// close the zip file
		$zip->close();

		// return the path to the file
		return $zipFile;
	}

	/**
	 * Create the JSON file of the json
	 * @author ricardo@apretaste.org
	 */
	private function createResponseJSON()
	{
		// $file = Utils::getTempDir()."data/".substr(md5(date('dHhms').rand()), 0, 8).".json";
		// file_put_jsons($file,$this->json);
		// $this->responseFile = $file;
	}
}
