<?php

use Phalcon\Mvc\Controller;

class RechargesController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * List of unpaid recharges
	 */
	public function indexAction()
	{
		// decide between just unpaid or all
		$all = $this->request->get('all');
		$where = empty($all) ? "WHERE paid IS NULL" : "";

		// get the unpaid recharges
		$recharges = Connection::query("
			SELECT A.id, A.cellphone, A.inserted, A.paid, B.email, B.username, C.name, C.price
			FROM _recargas A
			JOIN person B
			JOIN inventory C
			ON A.person_id = B.id
			AND A.product_code = C.code
			$where
			ORDER BY A.inserted ASC");

		// send data to the view
		$this->view->title = "Recharges";
		$this->view->recharges = $recharges;
		$this->view->buttons = [
			["caption"=>"Unpaid", "href"=>"/recharges"],
			["caption"=>"All", "href"=>"/recharges/?all=1"]
		];
	}

	/**
	 * check a recharge as paid and return
	 */
	public function payAction()
	{
		// get the ID of the recharges
		$id = $this->request->get('id');

		// get the person logged in
		$security = new Security();
		$manager = $security->getUser();

		// update the recharge as paid
		Connection::query("
			UPDATE _recargas SET 
			paid = CURRENT_TIMESTAMP,
			paid_by = '{$manager->email}'
			WHERE id = '$id'");

		// redirect to the list of recharges
		$this->response->redirect("recharges");
	}
}