<?php

class Response
{
	public $email;
	public $subject;
	public $template;
	public $content;
	public $json; // will be null unless the return is an email API
	public $images;
	public $attachments;
	public $internal; // false if the user provides the template
	public $render; // false if the response should not be email to the user
	public $ads;
	public $layout;

	/**
	 * Create default template
	 *
	 * @author salvipascual
	 */
	public function __construct()
	{
		$this->template = "message.tpl";
		$this->content = array("text"=>"<b>Warning:</b> Default responses will never be emailed to the user.");
		$this->images = array();
		$this->attachments = array();
		$this->layout = "email_default.tpl";

		$this->json = null;
		$this->internal = true;
		$this->render = false;
		$this->ads = array();
	}

	/**
	 * Set the subject for the response
	 *
	 * @author salvipascual
	 * @param String $subject
	 * */
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
	 * */
	public function setResponseEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Set the global layout to control how the email looks
	 * All layouts are from app/controller/layouts
	 *
	 * @author salvipascual
	 * @param String $layout
	 * */
	public function setEmailLayout($layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Get the array of ads selected to be displayed
	 *
	 * @author salvipascual
	 * @return Object[]
	 * */
	public function getAds()
	{
		if (is_null($this->ads) || empty($this->ads)) $this->ads = $this->getAdsToShow();
		return $this->ads;
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
		$this->ads = $this->getAdsToShow();
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
		$this->ads = array();
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
		$this->ads = $this->getAdsToShow();
		return $this;
	}

	/**
	 * Automatically select two ads to be displayed
	 *
	 * @author salvipascual
	 * */
	private function getAdsToShow()
	{
		// get the array of ads from the database
		$connection = new Connection();
		$utils = new Utils();

		// get the person from the current email
		$person = $utils->getPerson($this->email);
		if ($person == false) $person = new stdClass();
		if ( ! isset($person->age)) $person->age = null;
		if ( ! isset($person->gender)) $person->gender = null;
		if ( ! isset($person->eyes)) $person->eyes = null;
		if ( ! isset($person->skin)) $person->skin = null;
		if ( ! isset($person->body_type)) $person->body_type = null;
		if ( ! isset($person->hair)) $person->hair = null;
		if ( ! isset($person->province)) $person->province = null;
		if ( ! isset($person->highest_school_level)) $person->highest_school_level = null;
		if ( ! isset($person->marital_status)) $person->marital_status = null;
		if ( ! isset($person->sexual_orientation)) $person->sexual_orientation = null;
		if ( ! isset($person->religion)) $person->religion = null;

		// select the ads to show
		$sql = "
			SELECT * FROM ads WHERE active=1
			AND expiration_date > CURRENT_TIMESTAMP
			AND (SELECT credit FROM person WHERE person.email = ads.owner) >= ads.price
			AND ads.owner <> '{$this->email}' ";
		if ( ! empty($person->age)) $sql .= " AND (from_age * 1 <= {$person->age} OR from_age = 'ALL') AND (to_age * 1 >= {$person->age} OR to_age = 'ALL') ";
		if ( ! empty($person->gender)) $sql .= " AND (gender = '{$person->gender}' OR gender = 'ALL') ";
		if ( ! empty($person->eyes)) $sql .= " AND (eyes = '{$person->eyes}' OR eyes = 'ALL') ";
		if ( ! empty($person->skin)) $sql .= " AND (skin = '{$person->skin}' OR skin = 'ALL') ";
		if ( ! empty($person->body_type)) $sql .= " AND (body_type = '{$person->body_type}' OR body_type = 'ALL') ";
		if ( ! empty($person->hair)) $sql .= " AND (hair = '{$person->hair}' OR hair = 'ALL') ";
		if ( ! empty($person->province)) $sql .= " AND (province = '{$person->province}' OR province = 'ALL') ";
		if ( ! empty($person->highest_school_level)) $sql .= " AND (highest_school_level = '{$person->highest_school_level}' OR highest_school_level = 'ALL') ";
		if ( ! empty($person->marital_status)) $sql .= " AND (marital_status = '{$person->marital_status}' OR marital_status = 'ALL') ";
		if ( ! empty($person->sexual_orientation)) $sql .= " AND (sexual_orientation = '{$person->sexual_orientation}' OR sexual_orientation = 'ALL') ";
		if ( ! empty($person->religion)) $sql .= " AND (religion = '{$person->religion}' OR religion = 'ALL') ";
		$sql .= " ORDER BY last_usage LIMIT 2;";
		$ads = $connection->deepQuery($sql);

		// if there are not active ads stop processing here
		if(count($ads)==0) return array();

		// get top and bottom ads
		$topAd = $ads[0];
		if (isset($ads[1])) $bottomAd = $ads[1];
		else $bottomAd = $topAd;

		// save last usage date for the selected ads in the database
		$connection->deepQuery("UPDATE ads SET last_usage = CURRENT_TIMESTAMP WHERE id = {$topAd->id};");
		$connection->deepQuery("UPDATE ads SET last_usage = CURRENT_TIMESTAMP WHERE id = {$bottomAd->id};");

		return array($topAd, $bottomAd);
	}
}
