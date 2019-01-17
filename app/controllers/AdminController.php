<?php

use Phalcon\Mvc\Controller;

class AdminController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * List of raffles
	 */
	public function rafflesAction()
	{
		// List of raffles
		$raffles = Connection::query("
			SELECT A.*, MD5(A.raffle_id) AS picture, COUNT(B.raffle_id) as tickets
			FROM raffle A
			LEFT JOIN ticket B
			ON A.raffle_id = B.raffle_id
			GROUP BY A.raffle_id, B.raffle_id
			ORDER BY end_date DESC");

		// get the current number of tickets
		$raffleCurrentTickets = Connection::query("SELECT COUNT(ticket_id) as tickets FROM ticket WHERE raffle_id IS NULL");
		if(empty($raffles[0]->tickets)) $raffles[0]->tickets = $raffleCurrentTickets[0]->tickets;

		// send values to the template
		$this->view->title = "List of raffles";
		$this->view->buttons = [["caption"=>"New raffle", "href"=>"/admin/createraffle", "icon"=>"plus"]];
		$this->view->raffles = $raffles;
	}

	/**
	 * Get the winners of the raffle
	 */
	public function submitGetWinnersAction()
	{
		// get the raffle id
		$id = $this->request->get("id");
		if(empty($id)) return false;

		// get list of possible winners
		$winners = Connection::query("SELECT email,COUNT(email) FROM ticket 
		WHERE raffle_id IS NULL AND email NOT IN(
			SELECT IFNULL(winner_1,'') as email FROM raffle UNION ALL 
			SELECT IFNULL(winner_2,'') FROM raffle UNION ALL 
			SELECT IFNULL(winner_3,'') FROM raffle) 
			GROUP BY email ORDER BY COUNT(email) DESC LIMIT 30");

		// get the three winners from their tickets
		$winner1 = $winners[rand(0, 10)]->email;
		$winner2 = $winners[rand(11,20)]->email;
		$winner3 = $winners[rand(21,29)]->email;

		// insert the raffle winners
		Connection::query("
			UPDATE raffle SET winner_1='$winner1', winner_2='$winner2', winner_3='$winner3' WHERE raffle_id='$id';
			UPDATE ticket SET raffle_id = '$id' WHERE raffle_id IS NULL;");

		// go to the list of raffles
		$this->response->redirect("admin/raffles");
	}

	/**
	 * create raffle
	 */
	public function createraffleAction()
	{
		if($this->request->isPost())
		{
			$description = $this->request->getPost("description");
			$startDate = $this->request->getPost("startDate") . " 00:00:00";
			$endDate = $this->request->getPost("endDate") . " 23:59:59";

			// insert the Raffle
			$insertRaffle = Connection::query("INSERT INTO raffle (item_desc, start_date, end_date) VALUES ('$description','$startDate','$endDate')");

			// get the last inserted raffle's id
			$queryGetRaffleID = "SELECT raffle_id FROM raffle WHERE item_desc = '$description' ORDER BY raffle_id DESC LIMIT 1";
			$getRaffleID = Connection::query($queryGetRaffleID);

			// get the picture name and path
			$wwwroot = $this->di->get('path')['root'];
			$fileName = md5($getRaffleID[0]->raffle_id);
			$picPath = "$wwwroot/public/raffle/$fileName.jpg";
			move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

			// go to the list of raffles
			$this->response->redirect("admin/raffles");
		}

		$this->view->title = "Create raffle";
		$this->view->buttons = [["caption"=>"Back", "href"=>"/admin/raffles"]];
	}

	/**
	 * Show the dropped emails for the last 7 days
	 * @author salvipascual
	 */
	public function droppedAction()
	{
		// get last 7 days of emails rejected
		$dropped = Connection::query("
			SELECT *
			FROM delivery_checked
			WHERE inserted > DATE_SUB(NOW(), INTERVAL 7 DAY)
			AND status <> 'ok'
			ORDER BY inserted DESC");

		$this->view->title = "Emails dropped (Last 7 days)";
		$this->view->dropped = $dropped;
	}

	/**
	 * Remove dropped emails
	 * @author salvipascual
	 */
	public function submitDeleteDroppedAction()
	{
		$userEmail = $this->request->get('email');

		if ($userEmail)
		{
			// delete the block
			Connection::query("DELETE FROM delivery_checked WHERE email='$userEmail'");

			// email the user user letting him know
			$email = new Email();
			$email->to = $userEmail;
			$email->subject = "Arregle un problema con su email";
			$email->body = "Hola. Me he percatado que por error su direccion de email estaba bloqueada en nuestro sistema. He corregido este error y ahroa deberia poder usar Aprtste sin problemas. Siento mucho este inconveniente, y muchas gracias por usar nuestra plataforma.";
			$email->send();
		}

		// go back
		$this->response->redirect("admin/dropped");
	}

	/**
	 * Delivery status
	 * @author salvipascual
	 */
	public function deliveryAction()
	{
        $email = $this->request->get('email');
        $id = Connection::query("SELECT id FROM person WHERE email='$email'");

        $delivery = array();
        if(isset($id[0])) {
            $id = $id[0]->id;

        $delivery = Connection::query("
            SELECT request_date, request_service, request_subservice, request_query, environment, delivery_code
            FROM delivery  
            WHERE id_person=$id
            ORDER BY request_date DESC
            LIMIT 100");
		}

		$this->view->title = 'Delivery';
		$this->view->email = $email;
		$this->view->delivery = $delivery;
	}

	/**
	 * Show list of coupons emails
	 * @author salvipascual
	 */
	public function couponsAction()
	{
		// get coupons
		$coupons = Connection::query("SELECT * FROM _cupones ORDER BY inserted DESC");

		// get coupons usage
		$numberCouponsUsed = [];
		$couponsUsage = Connection::query("
			SELECT 
				COUNT(B.id) AS `usage`, 
				A.coupon 
			FROM _cupones A 
			LEFT JOIN _cupones_used B 
			ON A.coupon = B.coupon
			GROUP BY A.coupon");
		foreach($couponsUsage as $c) $numberCouponsUsed[] = ["coupon"=>$c->coupon, "usage"=>$c->usage];

		// send data to the view
		$this->view->title = "Coupons";
		$this->view->buttons = [["caption"=>"New coupon", "href"=>"#", "icon"=>"plus", "modal"=>"newCoupon"]];
		$this->view->coupons = $coupons;
		$this->view->numberCouponsUsed = $numberCouponsUsed;
	}

	/**
	 * Save a new input email to the list
	 * @author salvipascual
	 */
	public function submitDeleteCouponAction()
	{
		$coupon = $this->request->get("coupon");
		Connection::query("DELETE FROM _cupones WHERE coupon = '$coupon'");
		$this->response->redirect('admin/coupons');
	}

	/**
	 * Save a new input email to the list
	 * @author salvipascual
	 */
	public function submitNewCouponAction()
	{
		// get params from the url
		$coupon = strtoupper($this->request->get("coupon"));
		$ruleNewUser = $this->request->get("rule_new_user");
		$ruleDeadline = $this->request->get("rule_deadline");
		$ruleLimit = $this->request->get("rule_limit");
		$prizeCredits = $this->request->get("prize_credits");

		// insert into the database
		Connection::query("INSERT INTO _cupones(coupon, rule_new_user, rule_deadline, rule_limit, prize_credits) VALUES ('$coupon','$ruleNewUser','$ruleDeadline','$ruleLimit','$prizeCredits')");

		// go back
		$this->response->redirect('admin/coupons');
	}

	/**
	 * Show list of input emails
	 * @author salvipascual
	 */
	public function inputAction()
	{
		// measure the effectiveness of each promoter
		$emails = Connection::query("SELECT * FROM delivery_input ORDER BY environment");

		// send data to the view
		$this->view->title = "Input emails";
		$this->view->buttons = [["caption"=>"New email", "href"=>"#", "icon"=>"plus", "modal"=>"newInputEmail"]];
		$this->view->emails = $emails;
	}

	/**
	 * Save a new input email to the list
	 * @author salvipascual
	 */
	public function submitNewInputEmailAction()
	{
		// get params from the url
		$email = $this->request->get("email");
		$environment = $this->request->get("environment");

		// get the list of nodes
		Connection::query("INSERT INTO delivery_input (email,environment) VALUES ('$email', '$environment')");

		// go back
		$this->response->redirect('admin/input');
	}

	/**
	 * Delete an input email
	 * @author salvipascual
	 */
	public function submitDeleteInputEmailAction()
	{
		// get params from the url
		$email = $this->request->get("email");

		// get the list of nodes
		Connection::query("DELETE FROM delivery_input WHERE email='$email'");

		// go back
		$this->response->redirect('admin/input');
	}

	/**
	 * Profile search
	 * @author salvipascual
	 */
	public function profilesearchAction()
	{
		$email = $this->request->get("email");

		// get the user profile
		$utils = new Utils();
		$email = (stripos($email,'@'))? $email:Utils::getEmailFromUsername($email);
		if ($email) $profile = Utils::getPerson($email);

		$this->view->email = $email;
		$this->view->profile = $profile;
		$this->view->title = "Search for a profile";
	}

	/**
	 * Exclude an user from Apretaste
	 * @author salvipascual
	 * @param String $email
	 */
	public function submitExcludeAction()
	{
		$email = $this->request->get("email");

		// unsubscribe from the emails
		$utils = new Utils();
		$utils->unsubscribeFromEmailList($email);

		// mark the user inactive in the database
		Connection::query("UPDATE person SET active=0 WHERE email='$email'");

		// email the user user letting him know
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = "Siento ver que se nos va";
		$sender->body = "Hola. A peticion suya le he excluido y ahora no debera recibir mas nuestra correspondencia. Si desea volver a usar Aprtate en un futuro, acceda a la plataforma y sera automaticamente incluido. Disculpa si le hemos causamos alguna molestia, y gracias por usar nuestra app. Siempre es bienvenido nuevamente.";
		$sender->send();

		// redirect back
		header("Location: profilesearch?email=$email");
	}

	/**
	 * Block or unblock an user from Apretaste
	 * @author ricardo
	 * @param Int $id
	 */
	public function submitBlockAction()
	{
		$email = $this->request->get("email");

		// mark the user inactive in the database
		Connection::query("UPDATE person SET blocked = (blocked ^ 1) WHERE email='$email'");

		// redirect back
		header("Location: profilesearch?email=$email");
	}

	/**
	 * Authorize Gmail accounts to send via the Gmail API
	 * @author salvipascual
	 */
	public function gmailAction()
	{
		// get the Gmail client
		$gmailClient = new GmailClient();
		$client = $gmailClient->getClient();

		// get gmails from the database
		$temp = Utils::getTempDir();
		$gmails = Connection::query("SELECT * FROM delivery_gmail");
		foreach ($gmails as $gmail) {
			$credentialsPath = $temp . 'gmailclient/' . str_replace(['_','-','@','.'], '', $gmail->email) . '.json';

			if(file_exists($credentialsPath)) $gmail->auth = false;
			else $gmail->auth = $client->createAuthUrl();
		}

		$this->view->gmails = $gmails;
		$this->view->buttons = [["caption"=>"Add Code", "href"=>"#", "modal"=>"addCode"]];
		$this->view->title = "Authorized gmails";
	}

	/**
	 * Connect Gmail accounts to send via Gmail API
	 * @author salvipascual
	 */
	public function gmailSubmitAction()
	{
		$email = $this->request->get("email");
		$authCode = $this->request->get("code");

		// get the Gmail client
		$gmailClient = new GmailClient();
		$client = $gmailClient->getClient();

		// exchange authorization code for an access token
		$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

		// store the credentials to the database IF no errors
		if(empty($accessToken['error'])) {
			$accessTokenJSON = json_encode($accessToken);
			Connection::query("UPDATE delivery_gmail SET sent=0, active=1, access_token='$accessTokenJSON', token_created=CURRENT_TIMESTAMP WHERE email='$email'");
		}
		else die($accessToken['error']);

		// redirect back
		header("Location: gmail");
	}
}
