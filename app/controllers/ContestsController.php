<?php

use Phalcon\Mvc\Controller;

class ContestsController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * Contests
	 *
	 * @author kuma
	 */
	public function indexAction()
	{
		$this->view->title = "Contests";
		$this->view->message = false;

		$sql = "SELECT * FROM _concurso;";

		$connection = new Connection();
		$this->view->contests = $connection->deepQuery($sql);
	}

	/**
	 * New contest page
	 *
	 * @author kuma
	 */
	public function addAction()
	{
		$this->view->title = "Add contest";
		$this->view->message = false;
		$this->view->message_type = 'success';
	}

	/**
	 * New contest page
	 *
	 * @author kuma
	 */
	public function addPostAction()
	{
		$wwwroot = $this->di->get('path')['root'];
		$utils = new Utils();
		$connection = new Connection();

		$this->view->title = "Add contest";
		$this->view->message = "The contest was inserted";
		$this->view->message_type = 'success';

		$title = $this->request->getPost("title");
		$body = $this->request->getPost("body");
		$end_date = $this->request->getPost("end_date");
		$end_hour = $this->request->getPost("end_hour");
		$prize1 = $this->request->getPost("prize1");
		$prize2 = $this->request->getPost("prize2");
		$prize3 = $this->request->getPost("prize3");

		$r = $connection->query("SELECT max(id) as m FROM _concurso;");
		$id = $r[0]->m + 1;

		// 1. get images
		$images  = $utils->getInlineImagesFromHTML($body, 'cid:'); // $body will be modified
		$contestFolder = $wwwroot."/public/contestsImages/$id";

		// 2. save concurso
		$body = base64_encode($body);
		$sql = "INSERT INTO _concurso (id, title, body, end_date, prize1, prize2, prize3) VALUES ('$id', '$title','$body','$end_date $end_hour','$prize1', '$prize2', '$prize3');";
		$connection->deepQuery($sql);

		// 3. save images
		if (!file_exists($contestFolder))
		{
			@mkdir($wwwroot."/public/contestsImages");
			@mkdir($contestFolder);
		}

		if (file_exists($contestFolder))
		{
			$connection->query("DELETE FROM _concurso_images WHERE contest = '$id';");

			foreach($images as $img)
			{
				file_put_contents($contestFolder."/{$img['filename']}", base64_decode($img['content']));
				$connection->query("INSERT INTO _concurso_images (filename, mime_type, contest) VALUES ('{$img['filename']}','{$img['type']}','$id');");
			}
		}

		$this->response->redirect("/contests");
	}

	private function getContestImages($id)
	{
		$connection = new Connection();
		$r = $connection->query("SELECT * FROM _concurso_images WHERE contest = '$id';");
		$wwwroot = $this->di->get('path')['root'];
		$images = [];

		if ($r !== false)
		{
			foreach ($r as $row)
			{   $imageContent = file_get_contents($wwwroot."/public/contestsImages/$id/{$row->filename}");
				$images[$row->filename] = ['filename' => $row->filename, 'type' => $row->mime_type, 'content' => base64_encode($imageContent)];
			}
		}

		return $images;
	}

	public function deleteAction($id)
	{

		$sql = "DELETE FROM _concurso WHERE id ='$id';";
		$connection = new Connection();
		$connection->deepQuery($sql);

		$this->response->redirect("/contests");
	}

	public function editAction($id)
	{
		$connection =  new Connection();
		$utils = new Utils();
		$sql = "SELECT * FROM _concurso WHERE id = '$id';";
		$r = $connection->query($sql);

		if (isset($r[0]))
		{
			$contest = $r[0];
			$contest->body = base64_decode($contest->body);
			$this->view->title = "Edit contest (#$id): <i>{$contest->title}</i>";
			$this->view->message = false;
			$this->view->message_type = 'success';

			$images = $this->getContestImages($id);
			$contest->body = $utils->putInlineImagesToHTML($contest->body, $images, 'cid:', '.jpeg');
			$parts = explode(' ', $contest->end_date);
			$contest->end_date = $parts[0];
			$contest->hour = substr($parts[1],0,5);
			$this->view->contest = $contest;
		}
	}

	public function editPostAction($id)
	{
		$wwwroot = $this->di->get('path')['root'];
		$connection =  new Connection();
		$utils = new Utils();
		$r = $connection->query("SELECT * FROM _concurso WHERE id = '$id';");

		if (isset($r[0]))
		{
			// update contest data
			$title = $this->request->getPost("title");
			$body = $this->request->getPost("body");
			$end_date = $this->request->getPost("end_date");
			$end_hour = $this->request->getPost("end_hour");
			$prize1 = $this->request->getPost("prize1");
			$prize2 = $this->request->getPost("prize2");
			$prize3 = $this->request->getPost("prize3");
			$winner1 = $this->request->getPost("winner1");
			$winner2 = $this->request->getPost("winner2");
			$winner3 = $this->request->getPost("winner3");

			// 1. clear old images
			$utils->rmdir("$wwwroot/public/contestsImages/$id");

			// 2. get contest images
			$images  = $utils->getInlineImagesFromHTML($body, 'cid:'); // $body will be modified
			$contestFolder = $wwwroot."/public/contestsImages/$id";
			$body = base64_encode($body);

			// 3. save concurso
			$sql = "UPDATE _concurso SET title = '$title', end_date = '$end_date $end_hour', body='$body', prize1='$prize1',prize2='$prize2',prize3='$prize3',  winner1 = '$winner1', winner2 = '$winner2', winner3 = '$winner3' WHERE id = '$id';";

			$connection->deepQuery($sql);

			// 4. save contest images
			if (!file_exists($contestFolder))
			{
				@mkdir($wwwroot."/public/contestsImages");
				@mkdir($contestFolder);
			}

			if (file_exists($contestFolder))
			{
				$connection->query("DELETE FROM _concurso_images WHERE contest = '$id';");

				foreach($images as $img)
				{
					file_put_contents($contestFolder."/{$img['filename']}", base64_decode($img['content']));
					$connection->query("INSERT INTO _concurso_images (filename, mime_type, contest) VALUES ('{$img['filename']}','{$img['type']}','$id');");
				}
			}

			$this->response->redirect("/contests");
		}
	}

	public function viewAction($id)
	{
		$connection =  new Connection();
		$utils = new Utils();
		$r = $connection->query("SELECT * FROM _concurso WHERE id = '$id';");
		if (isset($r[0]))
		{
			$contest = $r[0];
			$contest->body = base64_decode($contest->body);

			$images = $this->getContestImages($id);
			$contest->body = $utils->putInlineImagesToHTML($contest->body, $images, 'cid:', '.jpeg');

			$contest->winners = false;

			$winner1 = $utils->getPerson($contest->winner1);
			$winner2 = $utils->getPerson($contest->winner2);
			$winner3 = $utils->getPerson($contest->winner3);

			if ($winner1 !== false || $winner2 !== false || $winner2 !== false)
				$contest->winners = [];

			if ($winner1 !== false) $contest->winners[] = $winner1;
			if ($winner2 !== false) $contest->winners[] = $winner2;
			if ($winner3 !== false) $contest->winners[] = $winner3;

			$this->view->contest = $contest;

			$this->view->title = "Contest (#$id): <i>{$contest->title}</i>";
			$this->view->message = false;
			$this->view->message_type = 'success';
		}
	}
}
