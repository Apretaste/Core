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
					//print_r($picPath);
					//exit;
					move_uploaded_file($_FILES["pictureAds"]["tmp_name"], $picPath);
					
					// send email to the user with the deploy key
					$email = new Email();
					$email->sendEmail($adsOwner, "Your ads $adsTittle was inserted", "<h1>Ads insertes</h1><p>Your Ads $adsTittle was inserted on $today. </p><p>Thank you for using Apretaste</p>");

					// redirect to the upload page with success message 
					return $this->response->redirect("ads?m=Ads inserted successfully.");
			}
		}
	}
}
