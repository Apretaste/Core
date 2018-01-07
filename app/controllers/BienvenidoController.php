<?php

use Phalcon\Mvc\Controller;

class BienvenidoController extends Controller
{
	public function indexAction()
	{
		// get visitors
		$visits = Connection::query("
			SELECT
				count(id) as received,
				DATE_FORMAT(request_date,'%Y-%m') as inserted
			FROM delivery
			GROUP BY DATE_FORMAT(request_date,'%Y-%m')
			HAVING inserted <> DATE_FORMAT(curdate(), '%Y-%m')
			ORDER BY inserted DESC
			LIMIT 5");

		// format data for the chart
		$visitors = [];
		$visitorsPerMonth = 0;
		foreach($visits as $visit) {
			if($visit->received > $visitorsPerMonth) $visitorsPerMonth = $visit->received;
			$visitors[] = ["date"=>date("M Y", strtotime($visit->inserted)), "emails"=>$visit->received];
		}

		$this->view->visitors = array_reverse($visitors);
		$this->view->visitorsPerMonth = $visitorsPerMonth;
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->pick("index/bienvenido");
	}
}
