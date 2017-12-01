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
		$connection = new Connection();
		$raffles = $connection->query("
			SELECT A.*, MD5(A.raffle_id) AS picture, COUNT(B.raffle_id) as tickets
			FROM raffle A
			LEFT JOIN ticket B
			ON A.raffle_id = B.raffle_id
			GROUP BY B.raffle_id
			ORDER BY end_date DESC");

		// get the current number of tickets
		$raffleCurrentTickets = $connection->query("SELECT COUNT(ticket_id) as tickets FROM ticket WHERE raffle_id IS NULL");
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
		if(empty($id)) die("Error bad ID");

		// get list of possible winners
		$connection = new Connection();
		$winners = $connection->query("
			SELECT email, COUNT(email) AS cnt
			FROM ticket
			WHERE raffle_id IS NULL
			AND email NOT IN (SELECT DISTINCT winner_1 FROM raffle)
			GROUP BY email
			ORDER BY cnt DESC
			LIMIT 30");

		// get the three winners from their tickets
		$winner1 = $winners[rand(0, 10)]->email;
		$winner2 = $winners[rand(11,20)]->email;
		$winner3 = $winners[rand(21,29)]->email;

		// insert the raffle winners
		$connection->query("
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
			$connection = new Connection();
			$insertRaffle = $connection->query("INSERT INTO raffle (item_desc, start_date, end_date) VALUES ('$description','$startDate','$endDate')");

			// get the last inserted raffle's id
			$queryGetRaffleID = "SELECT raffle_id FROM raffle WHERE item_desc = '$description' ORDER BY raffle_id DESC LIMIT 1";
			$getRaffleID = $connection->query($queryGetRaffleID);

			// get the picture name and path
			$wwwroot = $this->di->get('path')['root'];
			$fileName = md5($getRaffleID[0]->raffle_id);
			$picPath = "$wwwroot/public/raffle/$fileName.jpg";
			move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

			// optimize the image
			$utils = new Utils();
			$utils->optimizeImage($picPath, 400);

			// go to the list of raffles
			$this->response->redirect("admin/raffles");
		}

		$this->view->title = "Create raffle";
		$this->view->buttons = [["caption"=>"Back", "href"=>"/admin/raffles"]];
	}

	/**
	 * List of services
	 */
	public function servicesAction()
	{
		$connection = new Connection();
		$services = $connection->query("SELECT * FROM service");

		$this->view->title = "List of services (".count($services).")";
		$this->view->services = $services;
	}

	/**
	 * List of ads
	 */
	public function adsAction()
	{
		$connection = new Connection();
		$ads = $connection->query("SELECT id, owner, time_inserted, title, clicks, impresions, paid_date, expiration_date FROM ads");

		$this->view->title = "List of ads";
		$this->view->buttons = [["caption"=>"New add", "href"=>"/admin/createad", "icon"=>"plus"]];
		$this->view->ads = $ads;
	}

	/**
	 * Manage the ads
	 */
	public function createadAction()
	{
		// handle the submit if an ad is posted
		if($this->request->isPost())
		{
			// getting post data
			$adsOwner = $this->request->getPost("owner");
			$adsTittle = $this->request->getPost("title");
			$adsDesc = $this->request->getPost("description");
			$adsPrice = $this->request->getPost('price');
			$today = date("Y-m-d H:i:s"); // date the ad was posted
			$expirationDay = date("Y-m-d H:i:s", strtotime("+1 months"));

			// insert the ad
			$connection = new Connection();
			$queryGetAdsID = $connection->query("INSERT INTO ads (owner, title, description, expiration_date, paid_date, price) VALUES ('$adsOwner','$adsTittle','$adsDesc', '$expirationDay', '$today', '$adsPrice')");

			// save the image
			$wwwroot = $this->di->get('path')['root'];
			$picPath = "$wwwroot/public/ads/".md5($queryGetAdsID).".jpg";
			move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);

			// optimize the image
			$utils = new Utils();
			$utils->optimizeImage($picPath, 728, 90);

			// confirm by email that the ad was inserted
			$email = new Email();
			$email->sendEmail($adsOwner, "Your ad is now running on Apretaste", "<h1>Your ad is running</h1><p>Your ad <b>$adsTittle</b> was set to run $today.</p><p>Thanks for advertising using Apretaste.</p>");
			$this->view->adMesssage = "Your ad was posted successfully";

			// go to the list of ads
			$this->response->redirect("admin/ads");
		}

		$this->view->title = "Create ad";
		$this->view->buttons = [["caption"=>"Back", "href"=>"/admin/ads"]];
	}

	/**
	 * Reports for the ads
	 *
	 * @author kuma
	 */
	public function adReportAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);

		$connection = new Connection();

		$ad = $connection->query("SELECT * FROM ads WHERE id = $id;");
		$this->view->ad = false;

		if ($ad !== false)
		{
			$week = array();

			// @TODO fix field name in database: ad_bottom to ad_bottom
			$sql = "SELECT WEEKDAY(request_time) as w,
					count(usage_id) as total
					FROM utilization
					WHERE (ad_top = $id OR ad_bottom = $id)
					and service <> 'publicidad'
					and DATE(request_time) >= CURRENT_DATE - 6
					GROUP BY w
					ORDER BY w";

			$r = $connection->query($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['impressions'] = $i->total;
				}
			}

			$sql = "
				SELECT
				WEEKDAY(request_time) as w,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and (subservice = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY w";

			$r = $connection->query($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['clicks'] = $i->total;
				}
			}

			$this->view->weekly = $week;

			$month = array();

			$sql = "
				SELECT
				MONTH(request_time) as m, count(usage_id) as total
				FROM utilization WHERE (ad_top = $id OR ad_bottom = $id)
				and service <> 'publicidad'
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY m";

			$r = $connection->query($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
					$month[$i->m]['impressions'] = $i->total;
				}
			}

			$sql = "
				SELECT
				MONTH(request_time) as m,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and (trim(subservice) = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY m";

			$r = $connection->query($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
						$month[$i->m]['clicks'] = $i->total;

				}
			}

			// join sql
			$jsql = "SELECT * FROM utilization INNER JOIN person ON utilization.requestor = person.email
			WHERE service = 'publicidad'
				and (subservice = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)";

			// usage by age
			$sql = "SELECT IFNULL(YEAR(CURDATE()) - YEAR(subq.date_of_birth), 0) as a, COUNT(subq.usage_id) as t FROM ($jsql) AS subq GROUP BY a;";
			$r = $connection->query($sql);

			$usage_by_age = array(
				'0-16' => 0,
				'17-21' => 0,
				'22-35' => 0,
				'36-55' => 0,
				'56-130' => 0
			);

			if ($r != false)
			{
				foreach($r as $item)
				{
					$a = $item->a;
					$t = $item->t;
					if ($a < 17) $usage_by_age['0-16'] += $t;
					if ($a > 16 && $a < 22) $usage_by_age['17-21'] += $t;
					if ($a > 21 && $a < 36) $usage_by_age['22-35'] += $t;
					if ($a > 35 && $a < 56) $usage_by_age['36-55'] += $t;
					if ($a > 55) $usage_by_age['56-130'] += $t;
				}
			}

			$this->view->usage_by_age = $usage_by_age;

			// usage by X (enums)
			$X = array('gender','skin','province','highest_school_level','marital_status','sexual_orientation','religion');

			foreach($X as $xx)
			{
				$usage = array();
				$r = $connection->query("SELECT subq.$xx as a, COUNT(subq.usage_id) as t FROM ($jsql) AS subq WHERE subq.$xx IS NOT NULL GROUP BY subq.$xx;");

				if ($r != false)
				{
					foreach($r as $item) $usage[$item->a] = $item->t;
				}

				$p = "usage_by_$xx";
				$this->view->$p = $usage;
			}

			$this->view->weekly = $week;
			$this->view->monthly = $month;
			$this->view->title = "Ad report";
			$this->view->buttons = [["caption"=>"Back", "href"=>"/admin/ads"]];
			$this->view->ad = $ad[0];
		}
	}

	/**
	 * Show the ads target
	 *
	 * @author kuma
	 */
	public function adTageringAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);
		$connection = new Connection();
		$ad = $connection->query("SELECT * FROM ads WHERE id = $id;");
		$this->view->ad = false;

		if ($ad !== false)
		{
			if ($this->request->isPost())
			{
				$sql = "UPDATE ads SET ";
				$go = false;
				foreach($_POST as $key => $value)
				{
					if (isset($ad[0]->$key))
					{
						$go  = true;
						$sql .= " $key = '{$value}', ";
					}
				}

				if ($go)
				{
					$sql = substr($sql,0,strlen($sql)-2);
					$sql .= "WHERE id = $id;";
					$connection->query($sql);
				}

				$ad = $connection->query("SELECT * FROM ads WHERE id = $id;");
			}

			$this->view->title ="Ad targeting";
			$this->view->buttons = [["caption"=>"Back", "href"=>"/admin/ads"]];
			$this->view->ad = $ad[0];
		}
	}

	/**
	 * add credits
	 *
	 * @author kuma
	 */
	public function addcreditAction()
	{
		if ($this->request->isPost())
		{
			$email = $this->request->getPost('email');
			$credit = $this->request->getPost('credit');

			// check if the person exists
			$utils = new Utils();
			$exist = $utils->personExist($email);

			// check all values are correct
			if(empty($exist) || empty($credit) || $credit <= 0) {
				$this->view->message = "Error incorrect email or amount";
				$this->view->messageType = 'danger';
			} else {
				// add credit
				$connection = new Connection();
				$connection->query("UPDATE person SET credit=credit+$credit WHERE email='$email'");
				$this->view->message = "User's credit updated successfull";

				// show ok message
				$this->view->message = "Credito agregado correctamente. <a href='/admin/profilesearch?email=$email'>Check user profile</a>.";
				$this->view->messageType = 'success';
			}
		}

		$this->view->title = "Add credit";
	}

	/**
	 * Show the dropped emails for the last 7 days
	 * @author salvipascual
	 */
	public function droppedAction()
	{
		// get last 7 days of emails rejected
		$connection = new Connection();
		$dropped = $connection->query("
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
			$connection = new Connection();
			$connection->query("DELETE FROM delivery_checked WHERE email='$userEmail'");

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

		$delivery = array();
		if($email) {
			$connection = new Connection();
			$delivery = $connection->query("
				SELECT id, request_date, request_service, request_subservice, request_query, environment, delivery_code
				FROM delivery
				WHERE user='$email'
				ORDER BY request_date DESC
				LIMIT 100");
		}

		$this->view->title = 'Delivery';
		$this->view->email = $email;
		$this->view->delivery = $delivery;
	}

	/**
	 * Show list of input emails
	 * @author salvipascual
	 */
	public function inputAction()
	{
		// measure the effectiveness of each promoter
		$connection = new Connection();
		$emails = $connection->query("SELECT * FROM delivery_input ORDER BY environment");

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
		$connection = new Connection();
		$connection->query("INSERT INTO delivery_input (email,environment) VALUES ('$email', '$environment')");

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
		$connection = new Connection();
		$connection->query("DELETE FROM delivery_input WHERE email='$email'");

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
		$profile = $utils->getPerson($email);

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
		$connection = new Connection();
		$connection->query("UPDATE person SET active=0 WHERE email='$email'");

		// email the user user letting him know
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = "Siento ver que se nos va";
		$sender->body = "Hola. A peticion suya le he excluido y ahora no debera recibir mas nuestra correspondencia. Si desea volver a usar Aprtate en un futuro, acceda a la plataforma y sera automaticamente incluido. Disculpa si le hemos causamos alguna molestia, y gracias por usar nuestra app. Siempre es bienvenido nuevamente.";
		$sender->send();

		// redirect back
		header("Location: profilesearch?email=$email");
	}
}
