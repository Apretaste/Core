<?php

use Phalcon\Mvc\Controller;

class WelcomeController extends Controller
{
	public function indexAction()
	{
		// START visitors
		$connection = new Connection();
		$visits = $connection->deepQuery("
			SELECT
				count(*) as received,
				DATE_FORMAT(request_time,'%Y-%m') as inserted
			FROM utilization
			GROUP BY DATE_FORMAT(request_time,'%Y-%m')
			HAVING inserted <> DATE_FORMAT(curdate(), '%Y-%m')
			ORDER BY inserted DESC
			LIMIT 5");
		$visitors = array();
		$visitorsPerMonth = 0;
		foreach($visits as $visit)
		{
			if($visit->received > $visitorsPerMonth) $visitorsPerMonth = $visit->received;
			$visitors[] = ["date"=>date("M Y", strtotime($visit->inserted)), "emails"=>$visit->received];
		}
		$visitors = array_reverse($visitors);
		// END visitors

		$this->view->visitors = $visitors;
		$this->view->visitorsPerMonth = $visitorsPerMonth;
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->stripePushibleKey = $this->di->get('config')['stripe']['pushible'];

		$this->view->pick("index/welcome");
	}

	public function aboutusAction()
	{
		$this->view->wwwhttp = $this->di->get('path')['http'];
		$this->view->title = "Meet our team";
		$this->view->setLayout('website');
		$this->view->pick(['index/aboutus']);
	}
}
