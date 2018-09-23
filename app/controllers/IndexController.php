<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
	public function indexAction()
	{
		// get the language to display
		$lang = $this->dispatcher->getParam("lang");
		if(empty($lang)) $lang = "es";

		// get visitors
		$currentMonth = date("Y-m");
		$visits = Connection::query("
			SELECT value, dated
			FROM summary 
			WHERE label = 'monthly_gross_traffic'
			AND dated < '$currentMonth'
			ORDER BY dated DESC
			LIMIT 5");

		// format data for the chart
		$visitors = [];
		$visitorsPerMonth = 0;
		foreach($visits as $visit) {
			if($visitorsPerMonth < $visit->value) $visitorsPerMonth = $visit->value;
			$visitors[] = ["date"=>$visit->dated, "emails"=>$visit->value];
		}

		// send data to the view
		$this->view->visitors = array_reverse($visitors);
		$this->view->visitorsPerMonth = $visitorsPerMonth;
		$this->view->wwwroot = $this->di->get('path')['root'];
		$this->view->pick("index/$lang");
	}

	public function teamAction()
	{
		$this->view->title = "Meet our team";
		$this->view->setLayout('website');
	}
}
