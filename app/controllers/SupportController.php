<?php

use Phalcon\Mvc\Controller;

class SupportController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout('manage');
	}

	/**
	 * List all the opened tickets
	 */
	public function indexAction()
	{
		// get the list of campaigns
		$connection = new Connection();
		$tickets = $connection->query("
			SELECT *
			FROM support_tickets
			WHERE status = 'NEW'
			OR status = 'PENDING'
			ORDER BY creation_date DESC");

		// get the list of macros
		$cans = $connection->query("SELECT id, name FROM support_cans");

		// create top buttons
		$this->view->buttons = [
			["caption"=>"New ticket", "href"=>"#", "icon"=>"plus", "modal"=>"newTicket"],
			["caption"=>"Search", "href"=>"#", "modal"=>"searchTickets"]
		];

		// send variables to the view
		$this->view->title = "Open tickets";
		$this->view->tickets = $tickets;
		$this->view->cans = $cans;
	}

	/**
	 * List all the opened tickets
	 */
	public function cansAction()
	{
		// get the list of campaigns
		$connection = new Connection();
		$cans = $connection->query("SELECT * FROM support_cans");

		// send variables to the view
		$this->view->title = "Macros";
		$this->view->buttons = [["caption"=>"New macro", "href"=>"#", "onclick"=>"newCanResponse();", "icon"=>"plus"]];
		$this->view->cans = $cans;
	}

	/**
	 * Create a new macro or update one
	 */
	public function updateCanResponseSubmitAction()
	{
		// get data from POST
		$id = $this->request->get("id");
		$name = $this->request->get("name");
		$body = $this->request->get("body");

		// create a new macro or update an existing one
		$connection = new Connection();
		if(empty($id)) $connection->query("INSERT INTO support_cans(name,body) VALUES ('$name','$body')");
		else $connection->query("UPDATE support_cans SET name='$name',body='$body' WHERE id=$id");

		// go to the list of tickets
		$this->response->redirect("support/cans");
	}

	/**
	 * Delete a macro
	 */
	public function deleteCanResponseSubmitAction()
	{
		// get data from POST
		$id = $this->request->get("id");

		// delete macro
		$connection = new Connection();
		$connection->query("DELETE FROM support_cans WHERE id=$id");

		// go to the list of tickets
		$this->response->redirect("support/cans");
	}

	/**
	 * Show all the responses for a ticket
	 */
	public function viewAction()
	{
		$email = $this->request->get("email");

		// get the list of campaigns
		$connection = new Connection();
		$chats = $connection->query("
			SELECT *
			FROM support_tickets
			WHERE `from` = '$email'
			OR requester = '$email'
			ORDER BY creation_date ASC");

		// do not continue if there are no tickets
		if(empty($chats)) {
			echo "No hay tickets creados para $email";
			$this->view->disable();
			return false;
		}

		// get the list of macros
		$cans = $connection->query("SELECT id, name FROM support_cans");

		// get info from the requestor
		$utils = new Utils();
		$person = $utils->getPerson($email);

		// send variables to the view
		$this->view->title = "View ticket";
		$this->view->chats = $chats;
		$this->view->email = $email;
		$this->view->name = $person ? (empty($person->first_name) ? "@{$person->username}" : $person->first_name) : "";
		$this->view->person = $person;
		$this->view->subject = "Re: " . $chats[count($chats)-1]->subject;
		$this->view->cans = $cans;
	}

	/**
	 * Save a ticket and send a new email
	 */
	public function saveTicketSubmitAction()
	{
		$to = $this->request->get("email");
		$subject = $this->request->get("subject");
		$content = $this->request->get("content");
		$status = $this->request->get("status");

		// update chats in the tickets with the status
		$connection = new Connection();
		$connection->query("
			UPDATE support_tickets
			SET status = 'DONE'
			WHERE (`from` = '$to' OR requester = '$to')
			AND (status = 'NEW' OR status = 'PENDING')");

		// get the manager information
		$security = new Security();
		$manager = $security->getUser();

		// save response in the database
		$subClean = $connection->escape($subject, 250);
		$conClean = $connection->escape($content, 1024);
		$connection->query("
			INSERT INTO support_tickets(`from`, subject, body, status, requester)
			VALUES ('{$manager->email}', '$subClean', '$conClean', '$status', '$to')");

		// send a notification to the user
		$utils = new Utils();
		$utils->addNotification($to, "soporte", "Hemos dado respuesta a una peticion que envio al soporte", "SOPORTE");

		// save report
		$mysqlDateToday = date('Y-m-d');
		$connection->query("
			INSERT IGNORE INTO support_reports (inserted) VALUES ('$mysqlDateToday');
			UPDATE support_reports SET response_count = response_count+1 WHERE inserted = '$mysqlDateToday';");

		// respond back to the user
		$email = new Email();
		$email->to = $to;
		$email->subject = $subject;
		$email->body = str_replace("\r", "<br/>", $content);
		$email->send();

		// go to the list of tickets
		$this->response->redirect("support/index");
	}

	/**
	 * Close a ticket without responding
	 */
	public function closeTicketSubmitAction()
	{
		$email = $this->request->get("email");

		// update chats in the tickets with the status
		$connection = new Connection();
		$connection->query("
			UPDATE support_tickets
			SET status = 'CLOSED'
			WHERE (`from` = '$email' OR requester = '$email')
			AND (status = 'NEW' OR status = 'PENDING')");

		// save report
		$mysqlDateToday = date('Y-m-d');
		$connection->query("
			INSERT IGNORE INTO support_reports (inserted) VALUES ('$mysqlDateToday');
			UPDATE support_reports SET closed_count = closed_count+1 WHERE inserted = '$mysqlDateToday';");

		// go to the list of tickets
		$this->response->redirect("support/index");
	}

	/**
	 * Load the can text async
	 */
	public function loadCanResponseAsyncAction()
	{
		$id = $this->request->get("id");
		$name = $this->request->get("name");
		$username = $this->request->get("username");

		// get the list of macros
		$connection = new Connection();
		$can = $connection->query("SELECT body FROM support_cans WHERE id=$id");
		$content = $can[0]->body;

		// add manager name and position
		$security = new Security();
		$manager = $security->getUser();
		$content = str_replace('{MANAGER_NAME}', $manager->name, $content);
		$content = str_replace('{MANAGER_POSITION}', $manager->position, $content);
		$content = str_replace('{USER_NAME}', $name, $content);
		$content = str_replace('{USER_USERNAME}', "@$username", $content);
		$content = str_replace('{USER_EMAIL}', "apretaste+{$username}@gmail.com", $content);

		// get the email address of the support
		$utils = new Utils();
		$supportEmail = $utils->getSupportEmailAddress();
		$content = str_replace('{SUPPORT_EMAIL}', $supportEmail, $content);

		// return final text
		echo $content;
		$this->view->disable();
	}

	/**
	 * Show reports by tickets
	 */
	public function reportsAction()
	{
		// get total of new tickets
		$connection = new Connection();
		$unresponded = $connection->query("
			SELECT COUNT(id) AS count
			FROM support_tickets
			WHERE status = 'NEW'
			OR status = 'PENDING'")[0]->count;

		// get the last 30 days of tickets
		$mysqlDateLastMonth = date('Y-m-d', strtotime('last month'));
		$tickets = $connection->query("
			SELECT *
			FROM support_reports
			WHERE inserted > '$mysqlDateLastMonth'
			ORDER BY inserted DESC");

		// send variables to the view
		$this->view->title = "Tickets ($unresponded)";
		$this->view->tickets = $tickets;
	}

	/**
	 * Notes not responded
	 */
	public function notesAction()
	{
		$notes = self::getUnrespondedNotesToApretaste();
		$this->view->title = "Open chats (".count($notes).")";
		$this->view->notes = $notes;
	}

	/**
	 * Post the response
	 */
	public function noteSubmitAction()
	{
		$email = $this->request->get("email");
		$text = $this->request->get("text");

		// store the note in the database
		$connection = new Connection();
		$text = $connection->escape($text, 499);
		$connection->query("INSERT INTO _note (from_user, to_user, `text`) VALUES ('salvi@apretaste.com','$email','$text')");

		// send notification for the app
		$utils = new Utils();
		$friendEmail = $utils->getUsernameFromEmail($email);
		$utils->addNotification($friendEmail, "chat", "@apretaste le ha enviado una nota", "CHAT @apretaste");

		// go to the list of notes
		$this->response->redirect("support/notes");
	}

	/**
	 * Conversation between Apretaste and somebody
	 */
	public function conversationAction()
	{
		$email = $this->request->get("email");

		$social = new Social();
		$notes = $social->chatConversation("salvi@apretaste.com", $email);

		$this->view->title = "Chat";
		$this->view->notes = $notes;
		$this->view->email = $email;
		$this->view->buttons = [["caption"=>"Go back", "href"=>"notes"]];
	}

	/**
	 * Get unread notes sent to Apretaste but never responded
	 */
	public static function getUnrespondedNotesToApretaste()
	{
		return Connection::query("
			SELECT i.from_user AS email, IF(o.last_chat IS NULL, i.last_chat, o.last_chat) AS last_chat
			FROM (SELECT from_user, MAX(send_date) AS last_chat FROM _note WHERE to_user='salvi@apretaste.com' GROUP BY from_user) i
			LEFT JOIN (SELECT to_user, MAX(send_date) AS last_chat FROM _note WHERE from_user='salvi@apretaste.com' GROUP BY to_user) o
			ON o.to_user = i.from_user
			WHERE o.to_user IS NULL
			OR o.last_chat < i.last_chat
			ORDER BY last_chat DESC");
	}
}
