<?php

use Phalcon\Mvc\Controller;

class PizarraController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * List of uncataloged notes
	 */
	public function notesAction()
	{
		// get the last 100 unclassified notes
		$notes = Connection::query("
			SELECT *
			FROM _pizarra_notes
			WHERE reviewed IS NULL
			ORDER BY inserted DESC
			LIMIT 100");

		// get list of topics
		$topics = Connection::query("
			SELECT topic AS name, COUNT(id) AS cnt FROM _pizarra_topics
			WHERE created > DATE_ADD(NOW(), INTERVAL -30 DAY)
			AND topic <> 'general'
			GROUP BY topic ORDER BY cnt DESC LIMIT 100");

		// send values to the template
		$this->view->title = "Classify notes";
		$this->view->notes = $notes;
		$this->view->topics = $topics;
	}

	/**
	 * Submit the list of topics
	 */
	public function submitTopicsAction()
	{
		// get the params from the URL
		$id = $this->request->get("id");
		$topic1 = $this->request->get("topic1");
		$topic2 = $this->request->get("topic2");
		$topic3 = $this->request->get("topic3");

		// get manager's email
		$security = new Security();
		$manager = $security->getUser();
		$email = $manager->email;

		// update topics
		Connection::query("
			UPDATE _pizarra_notes
			SET topic1='$topic1', topic2='$topic2', topic3='$topic3', reviewed='$email', reviewed_date=CURRENT_TIMESTAMP
			WHERE id='$id'");

		// go back to the notes
		$this->response->redirect("pizarra/notes");
	}
}
