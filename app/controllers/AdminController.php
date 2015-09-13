<?php

use Phalcon\Mvc\Controller;
  
class AdminController extends Controller
{
	public function indexAction()
	{
		//Include simple.phtml Layout
		$this->view->setLayout('simple');
	}
	
	public function raffleAction() 
	{
		$this->view->setLayout('simple');
		$this->view->createraffleError = $this->request->get("e");
		$this->view->createraffleMesssage = $this->request->get("m");
	}

	public function submitRaffleAction()
	{
		if($this->request->isPost())
		{
			$raffleDescription = $this->request->getPost("raffleDescription");
			$raffleStartDate = $this->request->getPost("raffleStartDate");
			$raffleFinishDate = $this->request->getPost("raffleFinishDate");

			//Insert the Raffle
			$connection = new Connection();
			$queryInsertRaffle = "INSERT INTO raffle (item_desc, start_date, end_date)
								  VALUES ('$raffleDescription','$raffleStartDate','$raffleFinishDate')";
			$insertRaffle = $connection->deepQuery($queryInsertRaffle);

			if($insertRaffle == NULL) //Inserted correctly
			{
				$queryGetRaffleID = "SELECT raffle_id
									 FROM raffle
									 WHERE item_desc = '$raffleDescription'
									 ORDER BY raffle_id DESC LIMIT 1";
				$getRaffleID = $connection->deepQuery($queryGetRaffleID);
				$pictureFile = $this->request->getUploadedFiles(); //Get the file uploaded
				$fileName = md5($getRaffleID[0]->raffle_id); //Generate the picture name

				// get the picture name and path
				$wwwroot = $this->di->get('path')['root'];
				$picPath = "$wwwroot/public/raffle/" . $fileName . ".png";
				move_uploaded_file($_FILES["pictureRaflle"]["tmp_name"], $picPath);

				// redirect to the upload page with success message 
				return $this->response->redirect("admin/raffle?m=Raffle inserted successfully.");
			}

			// redirect to the upload page with error message 
			return $this->response->redirect("admin/raffle?m=Raffle was unable to be inserted.");
		}
	}
	
	public function raffleListAction()
	{
		//Include simple.phtml Layout
		$this->view->setLayout('simple');

		$connection = new Connection();
		$queryraffleList = "SELECT item_desc, start_date, end_date, winner_1, winner_2, winner_3
							FROM raffle
							ORDER BY end_date DESC";
		$raffleListData = $connection->deepQuery($queryraffleList);

		$raffleListCollection = array();
		foreach($raffleListData as $raffleListItem)
		{
			$raffleListCollection[] = ["itemDesc"=>$raffleListItem->item_desc, "startDay"=>$raffleListItem->start_date, "finishDay"=>$raffleListItem->end_date, "winner1"=>$raffleListItem->winner_1, "winner2"=>$raffleListItem->winner_2, "winner3"=>$raffleListItem->winner_3];
		}

		$this->view->raffleListData = $raffleListCollection;
	}

	public function adsAction() 
	{
		$this->view->setLayout('simple'); 
		$this->view->createAdsError = $this->request->get("e");
		$this->view->createAdsMesssage = $this->request->get("m");
	}

	public function submitAdsAction()
	{
		//Ads form pass by post
		if($this->request->isPost())
		{
			$adsOwner = $this->request->getPost("owner");
			$adsTittle = $this->request->getPost("tittle");
			$AdsDesc = $this->request->getPost("description");
			
			$today = date("Y-m-d H:i:s"); //know what date and time it was inserted
			$expirationDay = date("Y-m-d H:i:s", strtotime("+1 months"));
			
			//Insert the Ads
			$connection = new Connection();
			$queryInsertAds = "INSERT INTO ads (owner, title, description, expiration_date, paid_date)
								VALUES ('$adsOwner','$adsTittle','$AdsDesc', '$expirationDay', '$today')";
			$insertAds = $connection->deepQuery($queryInsertAds);
			
			if($insertAds == NULL)
			{
				$queryGetAdsID = "SELECT ads_id
									FROM ads
									WHERE owner = '$adsOwner'
									ORDER BY ads_id DESC LIMIT 1";
				$getAdsID = $connection->deepQuery($queryGetAdsID);
				
				$pictureFile = $this->request->getUploadedFiles(); //Get the file uploaded
				$fileName = md5($getAdsID[0]->ads_id); //Generate the picture name

				// get the picture name and path
				$wwwroot = $this->di->get('path')['root'];
				$picPath = "$wwwroot/public/ads/" . $fileName . ".png";
				
				move_uploaded_file($_FILES["pictureAds"]["tmp_name"], $picPath);

				// send email to the user with the deploy key
				$email = new Email();
				$email->sendEmail($adsOwner, "Your ads $adsTittle was inserted", "<h1>Ads insertes</h1><p>Your Ads $adsTittle was inserted on $today. </p><p>Thank you for using Apretaste</p>");

				// redirect to the upload page with success message 
				return $this->response->redirect("admin/ads?m=Ads inserted successfully.");
			}
		}
	}
	
	public function jumperAction()
	{
		$this->view->setLayout('simple');
		$connection = new Connection();
		
		$queryJumper = "SELECT email, last_usage, sent_count, 'Errors' AS ErrorCount, blocked_domains, active
						FROM jumper, delivery_error";
		$jumperData = $connection->deepQuery($queryJumper);
		
		foreach($jumperData as $jumper)
			$jumperList[] = ["email" => $jumper->email, "lastUsage" => $jumper->last_usage, "emailsSent" => $jumper->sent_count, "errors" => $jumper->ErrorCount, "blockDomains" => $jumper->blocked_domains, "active" => $jumper->active];
		print_r($jumperList);
		exit;
		$this->view->jumperData = $jumperList;
	}

	public function toggleStatusAction()
	{
		if($this->request->get("email"))
		{
			$email = $this->request->get("email");
			$query = "UPDATE jumper SET active = !active WHERE email = '$email'";
			$connection = new Connection();
			$connection->deepQuery($query);
		}
		return $this->response->redirect('admin/jumper');
	}
}
