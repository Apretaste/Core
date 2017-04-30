<?php

use Phalcon\Mvc\Controller;

class CampaignsController extends Controller
{
	// do not let anonymous users pass
	public function initialize(){
		$security = new Security();
		$security->enforceLogin();
	}

	/**
	 * List all the opened campaigns
	 */
	public function indexAction()
	{
		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// get the list of campaigns
		$connection = new Connection();
		$campaigns = $connection->deepQuery("
			SELECT A.id, A.subject, A.sending_date, A.status, A.sent, B.name AS list, A.bounced
			FROM campaign A JOIN campaign_list B
			ON A.list = B.id
			WHERE A.`group` = '{$manager->group}'
			ORDER BY sending_date DESC");

		// send variables to the view
		$this->view->title = "List of campaigns";
		$this->view->campaigns = $campaigns;
		$this->view->setLayout('manage');
	}

	/**
	 * Show the html for one campaign
	 *
	 * @author salvipascual
	 */
	public function viewAction()
	{
		$id = $this->request->get("id");

		// get the list of campaigns
		$connection = new Connection();
		$campaign = $connection->deepQuery("
			SELECT subject, content, sending_date, status, sent, opened, bounced
			FROM campaign
			WHERE id = $id");

		// get the variables from the query
		$subject = $campaign[0]->subject;
		$content = $campaign[0]->content;

		// get the email or the user logged
		$security = new Security();
		$email = $security->getManager()->email;

		// create new response
		$response = new Response();
		$response->createFromHTML($content);

		// render the HTML
		$render = new Render();
		$service = new Service('campaign');
		$html = $render->renderHTML($service, $response);

		// send variables to the view
		$this->view->title = "View campaign";
		$this->view->email = $email;
		$this->view->html = $html;
		$this->view->campaign = $campaign[0];
		$this->view->setLayout('empty');
	}

	/**
	 * Creates a new campaign
	 */
	public function newAction()
	{
		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// get the lists
		$connection = new Connection();
		$lists = $connection->deepQuery("
			SELECT id, name
			FROM campaign_list
			WHERE `group` = '{$manager->group}'");

		// send variables to the view
		$this->view->title = "New campaign";
		$this->view->intent = "create";
		$this->view->manager = $manager;
		$this->view->id = "";
		$this->view->subject = "";
		$this->view->selectedList = "";
		$this->view->date = date("Y-m-d\T23:00");
		$this->view->lists = $lists;
		$this->view->layout = "";
		$this->view->setLayout('manage');
	}

	/**
	 * Updates a campaign in the database
	 */
	public function updateAction()
	{
		$id = $this->request->get("id");

		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// get the campaign from the database
		$connection = new Connection();
		$campaign = $connection->deepQuery("
			SELECT * FROM campaign
			WHERE id = $id
			AND status = 'WAITING'
			AND `group` = '{$manager->group}'");
		$campaign = $campaign[0];

		// get the lists
		$lists = $connection->deepQuery("
			SELECT id, name
			FROM campaign_list
			WHERE `group` = '{$manager->group}'");

		$wwwroot = $this->di->get('path')['root'];
		$campaignsFolder = "$wwwroot/public/campaign";
		$listPath = "$campaignsFolder/$id/images.list";

		$newImageList = [];
		if (file_exists($listPath))
		{
			$imagesList = unserialize(file_get_contents($listPath));
			foreach ($imagesList as $idImg => $img)
			{
				$img['content'] = base64_encode(file_get_contents("$campaignsFolder/$id/{$img['filename']}"));
				$newImageList[$idImg] = $img;
			}
		}

		$utils = new Utils();
		$campaign->content = $utils->putInlineImagesToHTML($campaign->content, $newImageList);

		// send variables to the view
		$this->view->title = "Edit campaign";
		$this->view->intent = "update";
		$this->view->manager = $manager;
		$this->view->id = $campaign->id;
		$this->view->subject = $campaign->subject;
		$this->view->selectedList = $campaign->list;
		$this->view->layout = $campaign->content;
		$this->view->lists = $lists;
		$this->view->date = date("Y-m-d\TH:i", strtotime($campaign->sending_date));

		$this->view->pick(['campaigns/new']);
		$this->view->setLayout('manage');
	}

	/**
	 * Show the lists of subscribers
	 */
	public function listsAction()
	{
		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// get the list of campaigns
		$connection = new Connection();
		$lists = $connection->deepQuery("SELECT * FROM campaign_list WHERE `group` = '{$manager->group}'");

		// calculate number of subscribers for all lists
		foreach ($lists as $list)
		{
			// for apretaste users in the mail list
			if($list->id == 1) {
				$count = $connection->deepQuery("SELECT COUNT(email) as cnt FROM person WHERE active=1 AND mail_list=1");
				$list->subscribers = $count[0]->cnt;
				continue;
			}

			// for all active apretaste users
			if($list->id == 2) {
				$count = $connection->deepQuery("SELECT COUNT(email) as cnt FROM person WHERE active=1");
				$list->subscribers = $count[0]->cnt;
				continue;
			}

			// all other lists
			$count = $connection->deepQuery("SELECT COUNT(id) as cnt FROM campaign_subscribers WHERE list='{$list->id}'");
			$list->subscribers = $count[0]->cnt;
		}

		// send variables to the view
		$this->view->title = "Campaign lists";
		$this->view->lists = $lists;
		$this->view->setLayout('manage');
	}

	/**
	 * Show all suscribers for a list
	 */
	public function subscribersAction()
	{
		$campaignId = $this->request->get("id");

		// get the list of campaigns
		$connection = new Connection();
		$subscribers = $connection->deepQuery("SELECT * FROM campaign_subscribers WHERE list = $campaignId");

		// send variables to the view
		$this->view->title = "Campaign subscriptors";
		$this->view->campaignId = $campaignId;
		$this->view->subscribers = $subscribers;
		$this->view->setLayout('manage');
	}

	/**
	 * Show reports by campaign
	 */
	public function reportsAction()
	{
		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// get the last 10 campaigns
		$connection = new Connection();
		$campaigns = $connection->deepQuery("
			SELECT id, subject, sending_date, status, sent, opened, bounced
			FROM campaign
			WHERE status = 'SENT'
			AND `group` = '{$manager->group}'
			ORDER BY sending_date ASC
			LIMIT 10");

		// send variables to the view
		$this->view->title = "Campaign reports";
		$this->view->campaigns = $campaigns;
		$this->view->setLayout('manage');
	}

	/**
	 * Add a list of subscribers to a list
	 */
	public function addSubscribersSubmitAction()
	{
		// get variables from POST
		$list = $this->request->getPost("list");
		$emails = $this->request->getPost("emails");

		// convert emails to array
		$emails = explode(PHP_EOL, $emails);

		// create sql with valid emails
		$sql = "INSERT IGNORE INTO campaign_subscribers (list, email) VALUES ";
		foreach ($emails as $email)
		{
			// check if email is valid and add to list
			$email = trim($email);
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) $sql .= "('$list', '$email'),";
		}
		$sql = rtrim($sql,","); // delete last comma

		// add to the database
		$connection = new Connection();
		$campaign = $connection->deepQuery($sql);

		// go to the list of campaigns
		$this->response->redirect("campaigns/subscribers/?id=$list");
	}

	/**
	 * Delete a subscriber from the list
	 */
	public function deleteSubscribersSubmitAction()
	{
		// get variables from POST
		$list = $this->request->get("list");
		$subscriberId = $this->request->get("id");

		// add to the database
		$connection = new Connection();
		$connection->deepQuery("DELETE FROM campaign_subscribers WHERE id='$subscriberId'");

		// go to the list of campaigns
		$this->response->redirect("campaigns/subscribers/?id=$list");
	}

	/**
	 * Add a new list and all its subscribers
	 */
	public function newListSubmitAction()
	{
		// get variables from POST
		$name = $this->request->getPost("name");
		$emails = $this->request->getPost("emails");

		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// convert emails to array
		$emails = explode(PHP_EOL, $emails);

		// add the list and the get the list ID
		$connection = new Connection();
		$listId = $connection->query("INSERT INTO campaign_list (name,`group`) VALUES ('$name', '{$manager->group}');");

		// create sql with valid emails
		$sql = "INSERT IGNORE INTO campaign_subscribers (list, email) VALUES ";
		foreach ($emails as $email)
		{
			// check if email is valid and add to list
			$email = trim($email);
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) $sql .= "('$listId', '$email'),";
		}

		// add to the database
		$sql = rtrim($sql,","); // delete last comma
		$campaign = $connection->deepQuery($sql);

		// go to the list
		$this->response->redirect("campaigns/lists");
	}

	/**
	 * Delete a whole list and all its subscribers
	 */
	public function deleteListSubmitAction()
	{
		// get variables from POST
		$list = $this->request->get("id");

		// add to the database
		$connection = new Connection();
		$connection->deepQuery("
			DELETE FROM campaign_list WHERE id='$list';
			DELETE FROM campaign_subscribers WHERE list='$list';");

		// go to the list of campaigns
		$this->response->redirect("campaigns/lists");
	}

	/**
	 * Create or update a campaign in the database
	 */
	public function saveCampaignSubmitAction()
	{
		// get variales from POST
		$id = $this->request->getPost("id"); // for when updating
		$subject = $this->request->getPost("subject");
		$content = $this->request->getPost("content");
		$list = $this->request->getPost("list");
		$date = $this->request->getPost("date");

		// minify the html and remove dangerous characters
		$utils = new Utils();
		$images  = $utils->getInlineImagesFromHTML($content);
		$wwwroot = $this->di->get('path')['root'];
		$campaignsFolder = "$wwwroot/public/campaign";

		// clean the HTML before saving
		/*$content = str_replace("'", "&#39;", $content);
		$content = preg_replace('/\s+/S', " ", $content);
		$content = $utils->clearHtml($content);
		*/
		//$p = strpos($content, '<body>');
		//$content = substr($content,str)

		// insert or update the campaign
		$connection = new Connection();
		if(empty($id))
		{
			// get details of the person logged
			$security = new Security();
			$manager = $security->getManager();

			// save the new campaign and get its id
			$sql = "
				INSERT INTO campaign (subject, list, `group`, content, sending_date)
				VALUES ('$subject','$list','{$manager->group}','$content', '$date')";
				
			$f = fopen("$wwwroot/logs/queries.sql","a");
			fputs($f, $sql."\n\n$content\n\n");
			fclose($f);
			$id = $connection->query($sql);
		}
		else
		{
			$connection->query("UPDATE campaign SET subject='$subject', list='$list', content='$content', sending_date='$date' WHERE id=$id");
			$utils->rmdir("$campaignsFolder/$id"); // clear old images
		}

		// save images
		if ( ! file_exists($campaignsFolder)) @mkdir($campaignsFolder);
		if ( ! file_exists("$campaignsFolder/$id")) @mkdir("$campaignsFolder/$id");
		if (file_exists("$campaignsFolder/$id"))
		{
			$imagesList = [];
			foreach($images as $idimg => $img)
			{
				file_put_contents($campaignsFolder."/$id/{$img['filename']}", base64_decode($img['content']));
				$itemImg = $img;
				unset($itemImg['content']);
				$imagesList[$idimg] = $itemImg;
			}

			file_put_contents("$campaignsFolder/$id/images.list", serialize($imagesList));
		}

		// go to the list of campaigns
		$this->response->redirect('campaigns/index');
	}

	/**
	 * deletes a campaign from the database
	 */
	public function removeCampaignSubmitAction()
	{
		$id = $this->request->get("id");

		// remove the campaign
		$connection = new Connection();
		$connection->deepQuery("DELETE FROM campaign WHERE id = $id");

		// remove images
		$utils = new Utils();
		$wwwroot = $this->di->get('path')['root'];
		$campaignsFolder = "$wwwroot/public/campaign";
		$utils->rmdir("$campaignsFolder/$id");

		// go back to the list of campaigns
		$this->response->redirect('campaigns/index');
	}

	/**
	 * Email a test campaign
	 */
	public function testAsyncAction()
	{
		// get the variables from the POST
		$email = $this->request->getPost("email");
		$subject = $this->request->getPost("subject");
		$content = $this->request->getPost("content");

		// create new response
		$response = new Response();
		$response->createFromHTML($content);

		// render the HTML
		$render = new Render();
		$service = new Service('campaign');
		$html = $render->renderHTML($service, $response);

		// send test email
		$sender = new Email();
		$sender->sendEmail($email, $subject, $html);

		// send the response
		echo '{result: true}';
		$this->view->disable();
	}

	/**
	 * Load a template file async
	 */
	public function loadTemplateAction()
	{
		// get the variables from the POST
		$template = $this->request->getPost("template");

		// path to the template
		$wwwroot = $this->di->get('path')['root'];
		$file = "$wwwroot/app/layouts/$template.tpl";

		// get the email campaign layout
		$layout = "";
		if(file_exists($file)) $layout = file_get_contents($file);

		// send the response
		echo $layout;
		$this->view->disable();
	}
}
