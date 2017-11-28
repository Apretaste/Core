<?php

class Response
{
	public $email;
	public $subject;
	public $template;
	public $content;
	public $json; // NULL unless the return is an email API
	public $html; // NULL unless passing a whole HTML
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
		$this->content = array("text"=>"Empty response");
		$this->images = array();
		$this->attachments = array();
		$this->json = null;
		$this->html = null;
		$this->internal = true;
		$this->render = false;

		// get the service that is calling me, if the object was created from inside a service
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$file = isset($trace[0]['file']) ? $trace[0]['file'] : "";
		if(php::endsWith($file, "/service.php")) $this->service = php::substring($file, "services/", "/service.php");

		// load the layout from the session, or pick default
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$layout = $di->getShared("session")->get("layout");
		$this->layout = empty($layout) ? "email_default.tpl" : $layout;
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
	public function setEmailLayout($layout="")
	{
		// get the layout file
		$utils = new Utils();
		$layout = $utils->getPathToService($this->service) . "/layouts/$layout";

		// save the layout in the session
		if(file_exists($layout)) {
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$di->getShared("session")->set("layout", $layout);
		}
		// set the default layout
		else $layout = "email_default.tpl";

		$this->layout = $layout;
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
		$this->content = array("code"=>$code, "message"=>$message, "text"=>$text);
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
		$this->template = "empty.tpl";
		$this->content = array();
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
	public function createFromTemplate($template, $content, $images=array(), $attachments=array())
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
	 * Build a response using a custom HTML instead of a template file
	 *
	 * @author salvipascual
	 * @param String $html
	 */
	public function createFromHTML($html, $images=array(), $attachments=array())
	{
		if(empty($content['code'])) $content['code'] = "ok"; // for the API

		$this->html = $html;
		$this->images = $images;
		$this->attachments = $attachments;
		$this->render = true;
		return $this;
	}
}
