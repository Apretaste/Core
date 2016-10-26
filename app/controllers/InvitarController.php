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

		if ( ! isset($_SESSION['phrase']))
			$_SESSION['phrase'] = uniqid(); // throw a die()

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

		// create classes needed
		$connection = new Connection();
		$email = new Email();
		$utils = new Utils();
		$render = new Render();

		// do not invite people who are already using Apretaste
		if($utils->personExist($guest))
		{
			$this->view->already = true;
			return $this->dispatcher->forward(array("controller"=>"invitar","action"=>"index"));
		}

		// send notification to the inviter
		$response = new Response();
		$response->setResponseSubject("Gracias por darle internet a un Cubano");
		$response->setEmailLayout("email_simple.tpl");
		$response->createFromTemplate("invitationThankYou.tpl", array('num_notifications' => 0));
		$response->internal = true;
		$html = $render->renderHTML(new Service(), $response);
		$email->sendEmail($inviter, $response->subject, $html);

		// send invitations to the guest
		$response = new Response();
		$response->setResponseSubject("$name le ha invitado a revisar internet desde su email");
		$responseContent = array("host"=>$name, "guest"=>$guest, 'num_notifications' => 0);
		$response->createFromTemplate("invitation.tpl", $responseContent);
		$response->internal = true;
		$html = $render->renderHTML(new Service(), $response);
		$email->sendEmail($guest, $response->subject, $html);

		// save all the invitations into the database at the same time
		$connection->deepQuery("INSERT INTO invitations (email_inviter,email_invited,source) VALUES ('$inviter','$guest','abroad')");

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
