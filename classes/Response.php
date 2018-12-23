<?php

class Response
{
	public $email;
	public $subject;
	public $template;
	public $content;
	public $json; // NULL unless the return is an email API
	public $images;
	public $attachments;
	public $internal; // false if the user provides the template
	public $render; // false if the response should not be email to the user
	public $layout;
	public $cache = 0;
	public $service = false;

	/**
	 * Create default template
	 *
	 * @author salvipascual
	 */
	public function __construct()
	{
		$this->template = "message.tpl";
		$this->content = ["text"=>"Empty response"];
		$this->images = [];
		$this->attachments = [];
		$this->json = null;
		$this->internal = true;
		$this->render = false;

		// get the service that is calling me, if the object was created from inside a service
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$file = isset($trace[0]['file']) ? $trace[0]['file'] : "";
		if(php::endsWith($file, "service.php")) $this->service = basename(dirname($file));

		// select the default layout
		$this->layout = "email_default.tpl";
	}

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
	 * Set the subject for the response
	 *
	 * @author salvipascual
	 * @param String $subject
	 */
	public function setResponseSubject($subject)
	{
		$this->subject = $subject;
	}

	/**
	 * Set the email of the response in the cases where is not the same as the requestor
	 * Useful for confirmations or for programmers to track actions/errors on their services
	 *
	 * @author salvipascual
	 * @param String $email
	 */
	public function setResponseEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Set the layout of the response
	 *
	 * @author salvipascual
	 * @param String $layout, empty to set default layout
	 */
	public function setEmailLayout($layout)
	{
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		// check if a public layout is passed
		$file = "$wwwroot/app/layouts/$layout";
		if(file_exists($file)) $this->layout = $file;

		// else, check if is a service layout
		else {
			$file = Utils::getPathToService($this->service) . "/layouts/$layout";
			if(file_exists($file)) $this->layout = $file;
		}
	}

	/**
	 * Build an HTML template response based on a text passed by the user
	 *
	 * @author salvipascual
	 * @param String, $text
	 * @param String, $code, error code if exist
	 * @param String, $message, error message if exist
	 */
	public function createFromText($text, $code="OK", $message="")
	{
		$this->template = "message.tpl";
		$this->content = ["code"=>$code, "message"=>$message, "text"=>$text];
		$this->internal = true;
		$this->render = true;
		return $this;
	}

	/**
	 * Receives a JSON text to be sent back by email. Used for email APIs
	 *
	 * @author salvipascual
	 * @param String, $json
	 */
	public function createFromJSON($json)
	{
		$this->json = $json;
		$this->internal = true;
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
	 * @param String[] $attachments, paths to the files to attach
	 */
	public function createFromTemplate($template, $content, $images=[], $attachments=[])
	{
		if(empty($content['code'])) $content['code'] = "ok"; // for the API

		$this->template = $template;
		$this->content = $content;
		$this->images = $images;
		$this->attachments = $attachments;
		$this->internal = false;
		$this->render = true;
		return $this;
	}

	/**
	 * Attach the service EJS templates
	 * 
	 * @author ricardo@apretaste.org
	 * @param Service $service
	 */

	public function attachTemplates($service){
		$serv_temp_dir = Utils::getTempDir()."templates/".$service->serviceName;
		if(!file_exists($serv_temp_dir)) mkdir($serv_temp_dir);
		$tpl_zip = $serv_temp_dir.'/'.$service->tpl_version.'.zip';
		if(!file_exists($tpl_zip)){
			$tpl_dir = $service->pathToService."/templates";
			$layout_dir = $service->pathToService."/layout";

			// create the zip file
			$zip = new ZipArchive;
			$zip->open($tpl_zip, ZipArchive::CREATE);

			$templates = array_diff(scandir($tpl_dir), array('..', '.'));
			foreach($templates as $tpl){
				$file = $tpl_dir."/$tpl";
				$zip->addFile($file,basename($file));
			}

			if(file_exists($layout_dir)){
				$layouts = array_diff(scandir($layout_dir), array('..', '.'));
				foreach($templates as $tpl){
					$file = $layout_dir."/$tpl";
					$zip->addFile($file,basename($file));
				}
			}
			$zip->close();
		}
		$this->attachments[] = $tpl_zip;
	}

	/**
	 * Attach the content of the response as a JSON
	 * @author ricardo@apretaste.org
	 */

	 public function attachContent(){
		$file = Utils::getTempDir()."data/".substr(md5(date('dHhms').rand()), 0, 8).".dat";
		$content = json_encode($this->content);
		file_put_contents($file,$content);
		$this->attachments[] = $file;
	 }
}
