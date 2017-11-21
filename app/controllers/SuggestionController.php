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
		$this->show();
	}

	/**
	 * Show approved suggestions
	 */
	public function approvedAction()
	{
		$this->show('APPROVED', "Approved suggestions");
	}

	/**
	 * Show discarded suggestions
	 */
	public function discardedAction()
	{
		$this->show('DISCARDED', "Discarded suggestions");
	}

	/**
	 * Generic method for show suggestions
	 *
	 * @param string $status
	 * @param string $title
	 */
	public function show($status = 'NEW', $title = "New suggestions")
	{
		$this->view->title   = '<i class="fa fa-comments"></i>&nbsp; ' . $title;
		$list                = Connection::query("SELECT * FROM _sugerencias_list WHERE status = '$status' ORDER BY inserted;");
		$this->view->list    = $list;
		$this->view->message = false;
	}

	/**
	 * Discard suggestion by id
	 *
	 * @param $id
	 */
	public function discardAction($id)
	{
		$r = Connection::query("SELECT * FROM _sugerencias_list WHERE id = $id;");
		if(isset($r[0]))
		{
			$email          = new Email();
			$email->to      = $r[0]->user;
			$email->subject = "Tu sugerencia ha sido rechazada";
			$email->sendFromTemplate("empty.tpl", ["text" => "Tu sugerencia <b>{$r[0]->text}</b> has sido rechazada por los moderadores."]);

			Connection::query("UPDATE _sugerencias_list SET status = 'DISCARDED', updated = now() WHERE id = $id;");
		}

		$this->response->redirect("/suggestion");
	}

	/**
	 * Approve suggestion by id
	 *
	 * @param $id
	 */
	public function approveAction($id)
	{
		Connection::query("UPDATE _sugerencias_list SET status = 'APPROVED', updated = now() WHERE id = $id;");
		$this->response->redirect("/suggestion");
	}
}