<?php

use Phalcon\Mvc\Controller;
  
class AnalyticsController extends Controller
{
	public function indexAction()
	{
	}

	public function homeAction()
	{
		// weecly visitors
		$visitorsWeecly = new gchart\gLineChart(700,200);
		$visitorsWeecly->addDataSet(array(112,315,66,40,321));
		$visitorsWeecly->addDataSet(array(212,115,366,140,569));
		$visitorsWeecly->setLegend(array("2014", "2015"));
		$visitorsWeecly->setColors(array("ff3344", "11ff11", "22aacc", "3333aa"));
		$visitorsWeecly->setVisibleAxes(array('x','y'));
		$visitorsWeecly->addAxisLabel(0, array("Sun", "Mon","Tue","Wed","Thr","Fri","Sat"));
		$visitorsWeecly->addAxisRange(1, 1, 569); // 1 to max

		// montly visitors
		$visitorsMonthly = new gchart\gLineChart(700,200);
		$visitorsMonthly->addDataSet(array(112,315,66,40,321,58,987,47,1354,564,987,123));
		$visitorsMonthly->addDataSet(array(212,115,366,140,897,546,135,564,32,475,54,87));
		$visitorsMonthly->setLegend(array("2014", "2015"));
		$visitorsMonthly->setColors(array("ff3344", "11ff11", "22aacc", "3333aa"));
		$visitorsMonthly->setVisibleAxes(array('x','y'));
		$visitorsMonthly->addAxisLabel(0, array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"));
		$visitorsMonthly->addAxisRange(1, 1, 987); // 1 to max

		// new user per month
		$newUsers = new gchart\gLineChart(700,200);
		$newUsers->addDataSet(array(112,315,66,40,321,58,987,47,1354,564,987,123));
		$newUsers->addDataSet(array(212,115,366,140,897,546,135,564,32,475,54,87));
		$newUsers->setLegend(array("2014", "2015"));
		$newUsers->setColors(array("ff3344", "11ff11", "22aacc", "3333aa"));
		$newUsers->setVisibleAxes(array('x','y'));
		$newUsers->addAxisLabel(0, array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"));
		$newUsers->addAxisRange(1, 1, 987); // 1 to max

		// get services usage monthly
		$servicesUsageMonthly = new gchart\gPieChart();
		$servicesUsageMonthly->addDataSet(array(112,315,66,40));
		$servicesUsageMonthly->setLegend(array("clima", "wikipedia", "revolico","chiste"));
		$servicesUsageMonthly->setColors = array("ff3344", "11ff11", "22aacc", "3333aa");

		// active domains monthly
		$activeDomainsMonthly = new gchart\gPieChart();
		$activeDomainsMonthly->addDataSet(array(112,315,66,40));
		$activeDomainsMonthly->setLegend(array("nauta.cu", "infomed.sld.cu", "enet.co.cu","cubanacan.cu"));
		$activeDomainsMonthly->setColors = array("ff3344", "11ff11", "22aacc", "3333aa");

		// bounce rate
		$bounceRateMontly = new gchart\gBarChart(700,200,'g');
		$bounceRateMontly->addDataSet(array(112,315,66,40,321,58,987,47,1354,564,987,123));
		$bounceRateMontly->addDataSet(array(212,115,366,140,897,546,135,564,32,475,54,87));
		$bounceRateMontly->setColors(array("ff3344", "11ff11", "22aacc"));
		$bounceRateMontly->setLegend(array("2014", "2015"));
		$bounceRateMontly->addAxisLabel(0, array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"));
		$bounceRateMontly->setAutoBarWidth();

		// updated profiles
		$updatedProfilesMontly = new gchart\gBarChart(700,200,'g');
		$updatedProfilesMontly->addDataSet(array(112,315,66,40,321,58,987,47,1354,564,987,123));
		$updatedProfilesMontly->addDataSet(array(212,115,366,140,897,546,135,564,32,475,54,87));
		$updatedProfilesMontly->setColors(array("ff3344", "11ff11", "22aacc"));
		$updatedProfilesMontly->setLegend(array("2014", "2015"));
		$updatedProfilesMontly->addAxisLabel(0, array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"));
		$updatedProfilesMontly->setAutoBarWidth();

		// send variables to the view
		$this->view->visitorsWeecly = $visitorsWeecly;
		$this->view->visitorsMonthly = $visitorsMonthly;
		$this->view->newUsers = $newUsers;
		$this->view->currentNumberOfActiveUsers = 261578;
		$this->view->servicesUsageMonthly = $servicesUsageMonthly;
		$this->view->activeDomainsMonthly = $activeDomainsMonthly;
		$this->view->bounceRateMontly = $bounceRateMontly;
		$this->view->updatedProfilesMontly = $updatedProfilesMontly;
		$this->view->currentNumberOfRunningaAds = 150;
	}
}
