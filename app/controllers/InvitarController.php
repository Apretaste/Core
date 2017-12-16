<?php

session_start();

use Phalcon\Mvc\Controller;
use Gregwar\Captcha\CaptchaBuilder;

/**
 * Invite user from the web
 *
 * @author kuma
 * @version 1.0
 */
class InvitarController extends Controller
{
	/**
	 * Invite from the web page
	 *
	 * @author kuma
	 * @version 1.0
	 */
	public function indexAction(){}

	/**
	 * Process the page when its submitted
	 *
	 * @author salvipascual
	 * @version 1.0
	 * */
	public function processAction()
	{
		// get the values from the post
		$captcha = trim($this->request->getPost('captcha'));
		$name = trim($this->request->getPost('name'));
		$host = trim($this->request->getPost('email'));
		$guest = trim($this->request->getPost('guest'));

		// display an error if no phrase in the session
		if ( ! isset($_SESSION['phrase'])) $_SESSION['phrase'] = uniqid();

		// check all values passed are valid
		if(
			strtoupper($captcha) != strtoupper($_SESSION['phrase']) ||
			$name == "" ||
			! filter_var($host, FILTER_VALIDATE_EMAIL) ||
			! filter_var($guest, FILTER_VALIDATE_EMAIL)
		){
			echo "Error procesando, por favor valla atras y comience nuevamente.";
			return false;
		}

		// params for the response
		$this->view->name = $name;
		$this->view->email = $host;

		// do not invite people who are already using Apretaste
		$utils = new Utils();
		if($utils->personExist($guest))
		{
			$this->view->already = true;
			return $this->dispatcher->forward(array("controller"=>"invitar","action"=>"index"));
		}

		// send email to the host
		$email = new Email();
		$email->to = $host;
		$email->subject = "Gracias por darle internet a un Cubano";
		$email->sendFromTemplate("invitationThankYou.tpl", array('num_notifications'=>0), "email_empty.tpl");

		// send email to the guest
		$email = new Email();
		$email->to = $guest;
		$email->subject = "$name quiere que descargue nuestra app";
		$email->sendFromTemplate("invitationAbroad.tpl", array("host"=>$name));

		// save all the invitations into the database at the same time
		$connection = new Connection();
		$connection->query("INSERT INTO invitations (host,guest) VALUES ('$host','$guest')");

		// redirect to the invite page
		$this->view->message = true;
		return $this->dispatcher->forward(array("controller"=>"invitar","action"=>"index"));
	}

	/**
	 * Publish CAPTCHA image
	 *
	 * @author kuma
	 */
	public function captchaAction()
	{
		$builder = new CaptchaBuilder();
		$builder->build();

		$_SESSION['phrase'] = $builder->getPhrase();

		header('Content-type: image/jpeg');
		$builder->output();
		$this->view->disable();
	}

	/**
	 * Ajax call to check if the captcha is ok
	 *
	 * @author salvipascual
	 * */
	public function checkAction()
	{
		$captcha = $this->request->get('text');
		if(strtoupper($captcha) == strtoupper($_SESSION['phrase'])) echo "true";
		else echo "false";
	}
}
