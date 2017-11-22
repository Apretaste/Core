<?php

use Phalcon\Mvc\Controller;

/**
 * Suggestion Controller
 *
 * @author kumahacker
 */
class SuggestionController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Default action, show new suggestions
	 */
	public function indexAction()
	{
		// get list of new suggestions
		$list = Connection::query("SELECT * FROM _sugerencias_list WHERE status='NEW' ORDER BY inserted DESC LIMIT 200");

		// show buttons
		$this->view->buttons = [
			["caption"=>"New", "href"=>"/suggestion"],
			["caption"=>"Approved", "href"=>"/suggestion/approved"],
			["caption"=>"Discarded", "href"=>"/suggestion/discarded"]
		];

		// send data to the view
		$this->view->title = "New suggestions";
		$this->view->list = $list;
	}

	/**
	 * Show approved suggestions
	 */
	public function approvedAction()
	{
		// get list of new suggestions
		$list = Connection::query("SELECT * FROM _sugerencias_list WHERE status='APPROVED' ORDER BY inserted DESC LIMIT 200");

		// show buttons
		$this->view->buttons = [
			["caption"=>"New", "href"=>"/suggestion"],
			["caption"=>"Approved", "href"=>"/suggestion/approved"],
			["caption"=>"Discarded", "href"=>"/suggestion/discarded"]
		];

		// send data to the view
		$this->view->title = "Approved suggestions";
		$this->view->list = $list;
	}

	/**
	 * Show discarded suggestions
	 */
	public function discardedAction()
	{
		// get list of new suggestions
		$list = Connection::query("SELECT * FROM _sugerencias_list WHERE status='DISCARDED' ORDER BY inserted DESC LIMIT 200");

		// show buttons
		$this->view->buttons = [
			["caption"=>"New", "href"=>"/suggestion"],
			["caption"=>"Approved", "href"=>"/suggestion/approved"],
			["caption"=>"Discarded", "href"=>"/suggestion/discarded"]
		];

		// send data to the view
		$this->view->title = "Discarded suggestions";
		$this->view->list = $list;
	}

	/**
	 * Approve suggestion by id
	 * @param $id
	 */
	public function approveAction($id)
	{
		Connection::query("UPDATE _sugerencias_list SET status='APPROVED', updated=CURRENT_TIMESTAMP WHERE id=$id;");
		$this->response->redirect("suggestion/index");
	}

	/**
	 * Discard suggestion by id
	 * @param $id
	 */
	public function discardAction($id)
	{
		Connection::query("UPDATE _sugerencias_list SET status='DISCARDED', updated=CURRENT_TIMESTAMP WHERE id=$id;");
		$this->response->redirect("suggestion/index");
	}
}
