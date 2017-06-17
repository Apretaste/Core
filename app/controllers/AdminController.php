<?php

use Phalcon\Mvc\Controller;

class AdminController extends Controller
{
	// do not let anonymous users pass
	public function initialize(){
		$security = new Security();
		$security->enforceLogin();
	}

	/**
	 * Show the synonyms dictionary
	 */
	public function synonymsAction()
	{
		// get the list of campaigns
		$connection = new Connection();
		$synonyms = $connection->query("SELECT * FROM synonyms ORDER BY word ASC");

		// send variables to the view
		$this->view->title = "Synonyms";
		$this->view->synonyms = $synonyms;
		$this->view->setLayout('manage');
	}

	/**
	 * Insert/Update/Delete a new word and its synonyms
	 */
	public function manageWordSubmitAction()
	{
		// get data from the URL
		$word = strtolower($this->request->get("word"));
		$synonyms = strtolower($this->request->get("synonyms"));
		$type = $this->request->get("type");

		// create a new can response or update an existing one
		$connection = new Connection();
		if($type=="new") $connection->query("INSERT INTO synonyms (word,synonyms) VALUES ('$word','$synonyms')");
		if($type=="edit") $connection->query("UPDATE synonyms SET synonyms='$synonyms' WHERE word='$word'");
		if($type=="delete") $connection->query("DELETE FROM synonyms WHERE word = '$word'");

		// go to the list of tickets
		$this->response->redirect("admin/synonyms");
	}
}
