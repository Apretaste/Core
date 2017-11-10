<?php

use Phalcon\Mvc\Controller;

class CampaignsController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout('manage');
	}

	/**
	 * List all the opened campaigns
	 */
	public function indexAction()
	{
		// get the list of campaigns
		$connection = new Connection();
		$campaigns = $connection->query("SELECT * FROM campaign ORDER BY inserted DESC");

		// send variables to the view
		$this->view->title = "Campaigns";
		$this->view->buttons = [["caption"=>"New email", "href"=>"/campaigns/new", "icon"=>"plus"]];
		$this->view->campaigns = $campaigns;
	}

	/**
	 * Show the html for one campaign
	 * @author salvipascual
	 */
	public function viewAction()
	{
		$id = $this->request->get("id");

		// get the list of campaigns
		$connection = new Connection();
		$campaign = $connection->query("SELECT * FROM campaign WHERE id = $id");
		$campaign = $campaign[0];

		// send variables to the view
		$this->view->buttons = [["caption"=>"Go back", "href"=>"/campaigns"]];
		$this->view->title = $campaign->subject;
		$this->view->campaign = $campaign;
	}

	/**
	 * deletes a campaign from the database
	 */
	public function deleteSubmitAction()
	{
		$id = $this->request->get("id");

		// remove the campaign
		$connection = new Connection();
		$connection->query("DELETE FROM campaign WHERE id = $id");

		// go back to the list of campaigns
		$this->response->redirect('campaigns/index');
	}

	/**
	 * Creates a new campaign
	 */
	public function newAction()
	{
		// get details of the person logged
		$security = new Security();
		$manager = $security->getManager();

		// send variables to the view
		$this->view->title = "New campaign";
		$this->view->testEmail = $manager->email;
	}

	/**
	 * Create or update a campaign in the database
	 */
	public function newSubmitAction()
	{
		// get variales from POST
		$subject = $this->request->getPost("subject");
		$content = $this->request->getPost("content");

		// insert or update the campaign
		$connection = new Connection();
		$content = $connection->escape($content);
		$connection->query("INSERT INTO campaign (subject, content) VALUES ('$subject','$content')");

		// go to the list of campaigns
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

		// send test email
		$sender = new Email();
		$sender->to = $email;
		$sender->subject = $subject;
		$sender->body = $content;
		$sender->send();

		// send the response
		echo '{result: true}';
		$this->view->disable();
	}
}
