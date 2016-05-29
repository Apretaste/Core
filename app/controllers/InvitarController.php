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
		$this->view->setLayout('manage');
		$this->view->home = "/bienvenido";
		$this->view->title = "Invita un Cubano a Internet";
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
		$guest = $this->request->getPost('guest');

		// check all values passed are valid
		if(
			strtoupper($captcha) != strtoupper($_SESSION['phrase']) ||
			$name == "" || 
			! filter_var($inviter, FILTER_VALIDATE_EMAIL) ||
			! is_array($guest) || 
			empty($guest)
		) die("Error procesando, por favor valla atras y empiece nuevamente.");

		// create classes needed
		$connection = new Connection();
		$email = new Email();
		$utils = new Utils();
		$render = new Render();

		// send notification to the inviter
		$response = new Response();
		$response->setResponseSubject("Gracias por darle internet a un Cubano");
		$response->setEmailLayout("email_simple.tpl");
		$response->createFromTemplate("invitationThankYou.tpl", array());
		$response->internal = true;
		$html = $render->renderHTML(new Service(), $response);
		$email->sendEmail($inviter, $response->subject, $html);

		// send invitations to the Cubans
		$sql = "START TRANSACTION;";
		foreach ($guest as $g)
		{
			// check the email is fully valid
			$guestEmail = trim($g);
			if( ! filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) continue;

			// do not invite people who were already invited
			if($utils->checkPendingInvitation($guestEmail)) continue;

			// do not invite people who are already using Apretaste
			if($utils->personExist($guestEmail)) continue;

			// send invitation
			$response = new Response();
			$response->setResponseSubject("$name le ha invitado a revisar internet desde su email");
			$responseContent = array("host"=>$name, "guest"=>$guestEmail);
			$response->createFromTemplate("invitation.tpl", $responseContent);
			$response->internal = true;
			$html = $render->renderHTML(new Service(), $response);
			$email->sendEmail($guestEmail, $response->subject, $html);

			// create query to save invitation into the database
			$sql .= "INSERT INTO invitations (email_inviter,email_invited,source) VALUES ('$inviter','$guestEmail','abroad');";
		}

		// save all the invitations into the database at the same time
		$connection->deepQuery($sql."COMMIT;");

		// redirect to the invite page
		$this->view->name = $name;
		$this->view->email = $inviter;
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