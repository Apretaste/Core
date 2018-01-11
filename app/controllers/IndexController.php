<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
	public function indexAction()
	{
		// get the language to display
		$lang = $this->dispatcher->getParam("lang");
		if(empty($lang)) $lang = "es";

		// get path to the cache file
		$utils = new Utils();
		$cacheFile = $utils->getTempDir().date("Ym").".cache";

		// load from cache if exist
		if(file_exists($cacheFile))
		{
			$variables = explode("|", file_get_contents($cacheFile));
			$visitors = unserialize($variables[0]);
			$visitorsPerMonth = $variables[1];
		}
		// else get from the database
		else
		{
			// get visitors
			$connection = new Connection();
			$visits = $connection->query("
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
			$visitors = array_reverse($visitors);

			// create cache file
			file_put_contents($cacheFile, serialize($visitors)."|$visitorsPerMonth");
		}

		// send data to the view
		$this->view->visitors = $visitors;
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
