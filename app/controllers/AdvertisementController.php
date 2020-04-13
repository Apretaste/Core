<?php

use Phalcon\Mvc\Controller;

class AdvertisementController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * List of ads
	 *
	 * @author salvipascual
	 */
	public function indexAction()
	{
		// get list of ads
		$ads = Connection::query("
			SELECT id, owner, icon, title, clicks, impressions, inserted, expires, paid, active 
			FROM ads 
			-- WHERE active=1
			-- AND paid=1
			-- AND (expires IS NULL OR expires > CURRENT_TIMESTAMP)
			ORDER BY active DESC, paid, inserted DESC");

		// send values to the view
		$this->view->title = "List of ads";
		$this->view->buttons = [["caption"=>"New add", "href"=>"/advertisement/create", "icon"=>"plus"]];
		$this->view->ads = $ads;
	}

	/**
	 * Create a new ad
	 */
	public function createAction()
	{
		$this->view->title = "Create ad";
		$this->view->id = "";
		$this->view->owner = "";
		$this->view->icon = "";
		$this->view->title = "";
		$this->view->description = "";
		$this->view->expires = "";
		$this->view->icons = ['↓','$','☎','☻','♯','♫','♥','♦','★'];
		$this->view->buttons = [["caption"=>"Back", "href"=>"/advertisement/index"]];
	}

	/**
	 * Update an ad
	 */
	public function updateAction()
	{
		// get the current ad
		$id = $this->request->get("id");
		$ad = Connection::query("SELECT owner, icon, title, description, expires FROM ads WHERE id='$id'");

		// send values to the view
		$this->view->title = "Update ad";
		$this->view->id = $id;
		$this->view->owner = $ad[0]->owner;
		$this->view->icon = htmlentities($ad[0]->icon);
		$this->view->title = $ad[0]->title;
		$this->view->description = $ad[0]->description;
		$this->view->expires = date('Y-m-d', strtotime($ad[0]->expires));
		$this->view->icons = ['↓','$','☎','☻','♯','♫','♥','♦','★'];
		$this->view->buttons = [["caption"=>"Back", "href"=>"/advertisement/index"]];
		$this->view->pick('advertisement/create');
	}

	/**
	 * Submit the new ad
	 *
	 * @author salvipascual
	 */
	public function submitAction()
	{
		// getting data
		$id = $this->request->get("id");
		$owner = $this->request->get("owner");
		$icon = $this->request->get("icon");
		$title = $this->request->get("title");
		$description = $this->request->get("description");
		// $clicks = $this->request->get("clicks");
		// $impressions = $this->request->get("impressions");
		$expires = $this->request->get("expires");
		// $inserted = $this->request->get("inserted");
		// $active = $this->request->get("active");

		// prepare values to be saved
		$title = Connection::escape($title, 40);
		$description = Connection::escape($description, 500);
		$expires = empty($expires) ? 'NULL' : "'$expires'";

		// save the image
		$fileName = "";
		if($_FILES["picture"]["tmp_name"]) {
			$fileName = md5($icon . $title . $description . time()) . '.jpg';
			$picPath = $this->di->get('path')['root'] . "/public/ads/$fileName";
			move_uploaded_file($_FILES["picture"]["tmp_name"], $picPath);			
		}

		// update the ad at the database
		if($id) {
			$image = $fileName ? "image='$fileName'," : "";
			Connection::query("
				UPDATE ads 
				SET owner='$owner', icon='$icon', title='$title', description='$description', $image expires=$expires 
				WHERE id='$id'");
		// insert the ad in the database
		} else {
			Connection::query("
				INSERT INTO ads (owner,icon,title,description,image,expires)
				VALUES ('$owner','$icon','$title','$description','$fileName',$expires)");
		}

		// redirect to the list of ads
		$this->response->redirect("/advertisement/index");
	}

	/**
	 * Make an ad inactive
	 *
	 * @author salvipascual
	 */
	public function deleteAction()
	{
		// get id from the url
		$id = $this->request->get("id");

		// inactivate ad
		Connection::query("UPDATE ads SET active=0 WHERE id=$id");

		// redirect to the list of ads
		$this->response->redirect("/advertisement/index");
	}

	/**
	 * Make an ad active
	 *
	 * @author kumahacker
	 */
	public function playAction()
	{
		// get id from the url
		$id = $this->request->get("id");

		// inactivate ad
		Connection::query("UPDATE ads SET active=1 WHERE id=$id");

		// redirect to the list of ads
		$this->response->redirect("/advertisement/index");
	}

	/**
	 * Reports for the ads
	 * @TODO this Action does not work and we have to re-write it
	 *
	 * @author kuma
	 */
	public function reportAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);

		$ad = Connection::query("SELECT * FROM ads WHERE id = $id;");
		$this->view->ad = false;

		if ($ad !== false)
		{
			$week = array();

			// @TODO fix field name in database: ad_bottom to ad_bottom
			$sql = "SELECT WEEKDAY(request_time) as w,
					count(usage_id) as total
					FROM utilization
					WHERE (ad_top = $id OR ad_bottom = $id)
					and service <> 'publicidad'
					and DATE(request_time) >= CURRENT_DATE - 6
					GROUP BY w
					ORDER BY w";

			$r = Connection::query($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['impressions'] = $i->total;
				}
			}

			$sql = "
				SELECT
				WEEKDAY(request_time) as w,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and (subservice = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY w";

			$r = Connection::query($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($week[$i->w])) $week[$i->w] = array('impressions'=>0,'clicks'=>0);
					$week[$i->w]['clicks'] = $i->total;
				}
			}

			$this->view->weekly = $week;

			$month = array();

			$sql = "
				SELECT
				MONTH(request_time) as m, count(usage_id) as total
				FROM utilization WHERE (ad_top = $id OR ad_bottom = $id)
				and service <> 'publicidad'
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY m";

			$r = Connection::query($sql);

			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
					$month[$i->m]['impressions'] = $i->total;
				}
			}

			$sql = "
				SELECT
				MONTH(request_time) as m,
				count(usage_id) as total
				FROM utilization
				WHERE service = 'publicidad'
				and (trim(subservice) = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)
				GROUP BY m";

			$r = Connection::query($sql);
			if (is_array($r))
			{
				foreach($r as $i)
				{
					if ( ! isset($month[$i->m]))
						$month[$i->m] = array('impressions'=>0,'clicks'=>0);
						$month[$i->m]['clicks'] = $i->total;

				}
			}

			// join sql
			$jsql = "SELECT * FROM utilization INNER JOIN person ON utilization.requestor = person.email
			WHERE service = 'publicidad'
				and (subservice = '' OR subservice is NULL)
				and query * 1 = $id
				and YEAR(request_time) = YEAR(CURRENT_DATE)";

			// usage by age
			$sql = "SELECT IFNULL(YEAR(CURDATE()) - YEAR(subq.date_of_birth), 0) as a, COUNT(subq.usage_id) as t FROM ($jsql) AS subq GROUP BY a;";
			$r = Connection::query($sql);

			$usage_by_age = array(
				'0-16' => 0,
				'17-21' => 0,
				'22-35' => 0,
				'36-55' => 0,
				'56-130' => 0
			);

			if ($r != false)
			{
				foreach($r as $item)
				{
					$a = $item->a;
					$t = $item->t;
					if ($a < 17) $usage_by_age['0-16'] += $t;
					if ($a > 16 && $a < 22) $usage_by_age['17-21'] += $t;
					if ($a > 21 && $a < 36) $usage_by_age['22-35'] += $t;
					if ($a > 35 && $a < 56) $usage_by_age['36-55'] += $t;
					if ($a > 55) $usage_by_age['56-130'] += $t;
				}
			}

			$this->view->usage_by_age = $usage_by_age;

			// usage by X (enums)
			$X = array('gender','skin','province','highest_school_level','marital_status','sexual_orientation','religion');

			foreach($X as $xx)
			{
				$usage = array();
				$r = Connection::query("SELECT subq.$xx as a, COUNT(subq.usage_id) as t FROM ($jsql) AS subq WHERE subq.$xx IS NOT NULL GROUP BY subq.$xx;");

				if ($r != false)
				{
					foreach($r as $item) $usage[$item->a] = $item->t;
				}

				$p = "usage_by_$xx";
				$this->view->$p = $usage;
			}

			$this->view->weekly = $week;
			$this->view->monthly = $month;
			$this->view->title = "Ad report";
			$this->view->buttons = [["caption"=>"Back", "href"=>"/advertisement/index"]];
			$this->view->ad = $ad[0];
		}
	}
}
