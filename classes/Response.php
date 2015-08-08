<?php 

class Response {
	public $template;
	public $content;
	public $images;
	public $attachments;
	public $internal; // false if the user provides the template

	/**
	 * Create default template
	 *
	 * @author salvipascual
	 */
	public function __construct() {
		$this->template = "message.tpl";
		$this->content = array("text"=>"Por favor no responda a este email.");
		$this->images = array();
		$this->attachments = array();
		$this->internal = true;
	}

	/**
	 * Build an HTML template response based on a text passed by the user
	 *
	 * @author salvipascual
	 * @param String, $text
	 */
	public function createFromText($text) {
		$this->template = "message.tpl";
		$this->content = array("text"=>$text);
		$this->internal = true;
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
	public function createFromTemplate($template, $content, $images=array(), $attachments=array()) {
		$this->template = $template;
		$this->content = $content;
		$this->images = $images;
		$this->attachments = $attachments;
		$this->internal = false;
	}
}
