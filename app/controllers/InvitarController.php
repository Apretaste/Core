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
	public function indexAction()
	{
	}

	/**
	 * Process the page when its submitted
	 *
	 * @author kuma, salvipascual
	 * @version 1.0
	 * */
	public function processAction()
	{
		// get the values from the post
		$captcha = trim($this->request->getPost('captcha'));
		$name = trim($this->request->getPost('name'));
		$inviter = trim($this->request->getPost('email'));
		$guest = trim($this->request->getPost('guest'));

		// throw a die() if no phrase in the session
		if ( ! isset($_SESSION['phrase'])) $_SESSION['phrase'] = uniqid();

		// check all values passed are valid
		if(
			strtoupper($captcha) != strtoupper($_SESSION['phrase']) ||
			$name == "" ||
			! filter_var($inviter, FILTER_VALIDATE_EMAIL) ||
			! filter_var($guest, FILTER_VALIDATE_EMAIL)
		) die("Error procesando, por favor valla atras y comience nuevamente.");

		// params for the response
		$this->view->name = $name;
		$this->view->email = $inviter;

		// create classes
		$utils = new Utils();
		$render = new Render();

		// do not invite people who are already using Apretaste
		if($utils->personExist($guest))
		{
			$this->view->already = true;
			return $this->dispatcher->forward(array("controller"=>"invitar","action"=>"index"));
		}

		// create host response object
		$response = new Response();
		$response->email = $inviter;
		$response->setResponseSubject("Gracias por darle internet a un Cubano");
		$response->setEmailLayout("email_simple.tpl");
		$response->createFromTemplate("invitationThankYou.tpl", array('num_notifications' => 0));
		$response->internal = true;
		$html = $render->renderHTML(new Service(), $response);

		// send email to the host
		$email = new Email();
		$email->sendEmail($inviter, $response->subject, $html);

		// create guest response object
		$response = new Response();
		$response->email = $guest;
		$response->setEmailLayout("email_empty.tpl");
		$response->setResponseSubject("$name quiere que descargue nuestra app");
		$response->createFromTemplate("invitationAbroad.tpl", array("host"=>$name));
		$response->internal = true;
		$html = $render->renderHTML(new Service(), $response);

		// send email to the guest
		$email = new Email();
		$email->to = $response->email;
		$email->subject = $response->subject;
		$email->body = $html;
		$email->group = 'download';
		$email->send();

		// save all the invitations into the database at the same time
		$connection = new Connection();
		$connection->query("INSERT INTO invitations (email_inviter,email_invited,source) VALUES ('$inviter','$guest','abroad')");

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
		if(strtoupper($captcha) == strtoupper($_SESSION['phrase'])) die("true");
		else die("false");
	}
}
