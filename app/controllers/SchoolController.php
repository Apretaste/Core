<?php

use Phalcon\Mvc\Controller;

class SchoolController extends Controller
{
	// do not let anonymous users pass
	public function initialize(){
		$security = new Security();
		$security->enforceLogin();
	}

	/**
	 * List of school's courses
	 *
	 * @author kuma
	 */
	public function indexAction()
	{
		// get the current user's email
		$security = new Security();
		$manager = $security->getManager();
		$email = $manager->email;

		$connection = new Connection();
		$teachers = $connection->query("SELECT * FROM _escuela_teacher");

		$this->view->message = false;
		$this->view->message_type = 'success';
		$option = $this->request->get('option');
		$sql = false;

		if($this->request->isPost())
		{
			$title = $connection->escape($this->request->getPost("courseTitle"));
			$teacher = $connection->escape($this->request->getPost("courseTeacher"));

			if ( ! empty("$teacher"))
			{
				$content = $connection->escape($this->request->getPost("courseContent"));
				$category = $this->request->getPost('courseCategory');
				switch ($option){
					case 'add':
						$sql = "INSERT INTO _escuela_course (title, teacher, content, email, active, category) VALUES ('$title', '$teacher','$content','$email',0,'$category'); ";
						$this->view->message = 'The course was inserted successfull';
						break;
					case 'set':
						$id = $this->request->get('id');

						$setContent = "";
						if (isset($_POST['courseContent']))
						{
							$setContent = ", content = '$content'";
						}

						$sql = "UPDATE _escuela_course SET title = '$title', category = '$category', teacher = '$teacher' $setContent WHERE id = '$id'; ";

						$this->view->message = "The course <b>$title</b> was updated successfull";
						break;
				}
			}
			else
			{
				$this->view->message_type = 'danger';
				$this->view->message = 'You must select a teacher';
			}
		}

		switch ($option){
			case "del":
				$id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _escuela_answer WHERE course = '$id';
						DELETE FROM _escuela_question WHERE course = '$id';
						DELETE FROM _escuela_chapter WHERE course = '$id';
						DELETE FROM _escuela_course WHERE id = '$id';
						COMMIT;";
				$this->view->message = "The course #$id was deleted successfull";
				break;

			case "disable":
				$id = $this->request->get('id');
				$sql = "UPDATE _escuela_course SET active = 0 WHERE id ='$id';";
				break;
			case "enable":
				$id = $this->request->get('id');
				$sql = "UPDATE _escuela_course SET active = 1 WHERE id ='$id';";
				break;
		}

		if ($sql !== false)
		{
			$connection->query($sql);
		}

		$courses = $connection->query("SELECT * FROM _escuela_course ORDER BY ID");

		$this->view->title = "School";
		$this->view->courses = $courses;
		$this->view->teachers = $teachers;
		$this->view->setLayout('manage');
	}

	public function schoolTeachersAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';
		$option = $this->request->get('option');
		$sql = false;

		if($this->request->isPost())
		{
		   $name = $connection->escape($this->request->getPost("teacherName"));
		   $title = $connection->escape($this->request->getPost("teacherTitle"));
		   $email = $connection->escape($this->request->getPost("teacherEmail"));

		   switch ($option)
		   {
			case 'add':
				$sql = "INSERT INTO _escuela_teacher (name, title, email) VALUES ('$name', '$title', '$email'); ";
				$this->view->message = 'The teacher was inserted successful';
				break;
			case 'set':
				$id = $this->request->get('id');
				$sql = "UPDATE _escuela_teacher SET name = '$name', title = '$title', email = '$email' WHERE id = '$id'; ";
				$this->view->message = 'The teacher was updated successful';
				break;
			}
		}

		switch ($option)
		{
			case "del":
				$id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _escuela_teacher WHERE id = '$id';
						UPDATE _escuela_course SET teacher = null WHERE teacher = '$id';
						COMMIT;";
				$this->view->message = "The teacher #$id was deleted successful";
				break;
		}

		if ($sql !== false)
		{
			$connection->query($sql);
		}

		$teachers = $connection->query("SELECT * FROM _escuela_teacher;");

		if (!is_array($teachers))
		{
			$teachers = [];
		}

		$this->view->teachers = $teachers;
		$this->view->title = "School";
		$this->view->setLayout('manage');
	}

	/**
	 * List of chapters
	 *
	 * @author kuma
	 */
	public function schoolChaptersAction()
	{
		$wwwroot = $this->di->get('path')['root'];
		$connection = new Connection();
		$utils = new Utils();
		$this->view->message = false;
		$this->view->message_type = 'success';

		$course_id = intval($this->request->get('course'));
		$option = $this->request->get('option');

		switch ($option)
		{
			case "up":
				$id = $this->request->get('id');
				$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '$id';");
				if ($r !== false && isset($r[0]))
				{
					$chapter = $r[0];
					$connection->query("UPDATE _escuela_chapter SET xorder = xorder + 1 WHERE course = {$chapter->course} AND xorder = ". ($chapter->xorder - 1));
					$connection->query("UPDATE _escuela_chapter SET xorder = xorder - 1 WHERE id = $id AND xorder > 1;");
				}
				break;
			case "down":
				$id = $this->request->get('id');
				$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '$id';");
				if ($r !== false && isset($r[0]))
				{
					$chapter = $r[0];
					$max = $connection->query("SELECT max(xorder) as m FROM _escuela_chapter WHERE course = {$chapter->course};");
					$max = $max[0]->m;
					$connection->query("UPDATE _escuela_chapter SET xorder = xorder - 1 WHERE course = {$chapter->course} AND xorder = ". ($chapter->xorder + 1));
					$connection->query("UPDATE _escuela_chapter SET xorder = xorder + 1 WHERE id = $id AND xorder < $max;");

				}
				break;

			case "del":
				$id = $this->request->get('id');

				$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '$id';");
				if ($r !== false && isset($r[0]))
				{
					$chapter = $r[0];

					// remove images
					$utils->rmdir("$wwwroot/public/courses/{$chapter->course}/$id");

					$sql =
					"START TRANSACTION;" .
					"UPDATE _escuela_chapter SET xorder = xorder - 1 WHERE xorder > {$chapter->xorder} AND course = {$chapter->course};" .
					"DELETE FROM _escuela_chapter WHERE id = '$id';" .
					"DELETE FROM _escuela_question WHERE chapter = '$id';" .
					"DELETE FROM _escuela_images WHERE chapter = '$id';" .
					"COMMIT;";

					$connection->query($sql);
					$this->view->message = "The chapter #$id was deleted successful";
				}
				break;
		}

		$chapters = $connection->query("SELECT *, (SELECT count(_escuela_question.id) FROM _escuela_question WHERE chapter = s1.id) as questions FROM _escuela_chapter s1 WHERE course = '$course_id' ORDER BY xorder;");
		$r = $connection->query("SELECT * FROM _escuela_course WHERE id = '$course_id';");
		$course = $r[0];

		if (!is_array($chapters))
		{
			$chapters = [];
		}

		$this->view->course = $course;
		$this->view->chapters = $chapters;
		$this->view->title = 'Course: <i>' . $course->title . '</i>';
		$this->view->setLayout('manage');
	}

	/**
	 * New chapter page
	 *
	 * @author kuma
	 */
	public function schoolNewChapterAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';

		$course_id = intval($this->request->get('course'));
		$type = $this->request->get('type');

		if ($type !== 'CAPITULO' && $type !== 'PRUEBA')
		{
			$type = 'CAPITULO';
		}
		$r = $connection->query("SELECT * FROM _escuela_course WHERE id = '$course_id';");
		$course = $r[0];

		$this->view->course = $course;
		$this->view->type = $type;
		$this->view->course_id = $course_id;
		$this->view->title = $type == 'CAPITULO' ? 'New chapter for course <i>' . $course->title . '</i>' : 'New test for course <i>' . $course->title . '</i>';
		$this->view->setLayout('manage');
	}

	public function schoolNewChapterPostAction()
	{
		$wwwroot = $this->di->get('path')['root'];
		if ($this->request->isPost())
		{
			$connection = new Connection();
			$utils = new Utils();
			$imgExt = '.jpg';
			$chapterTitle = $connection->escape($this->request->getPost('title'));
			$chapterContent = $this->request->getPost('content');
			$images  = $utils->getInlineImagesFromHTML($chapterContent, 'cid:', $imgExt);
			$chapterContent = $connection->escape($chapterContent);
			$chapterType = $this->request->getPost('type');
			$course_id = intval($this->request->get('course'));
			$coursesFolder = $wwwroot."/public/courses";

			if ( ! file_exists($coursesFolder))
			{
				@mkdir($coursesFolder);
			}

			if ( ! file_exists("$coursesFolder/$course_id"))
			{
				@mkdir("$coursesFolder/$course_id");
			}

			$r = $connection->query("SELECT count(id) as total FROM _escuela_chapter WHERE course = '$course_id';");
			$order = intval($r[0]->total) + 1;

			if (isset($_GET['id']))
			{
				$id = $this->request->get('id');
				$sql = "UPDATE _escuela_chapter SET title = '$chapterTitle', content = '$chapterContent', xtype = '$chapterType' WHERE id = '$id';";
				$connection->query($sql);

				// clear old images
				$utils->rmdir("$wwwroot/public/courses/{$course_id}/$id");
			}
			else
			{
				$r = $connection->query("SELECT max(id) as m FROM _escuela_chapter;");
				$id = $r[0]->m + 1;
				$sql = "INSERT INTO _escuela_chapter (id, title, content, course, xtype, xorder) VALUES ($id, '$chapterTitle', '$chapterContent', '$course_id', '$chapterType', $order);";
				$connection->query($sql);
				//$r = $connection->query("SELECT LAST_INSERT_ID();");
				//$id = $id[0]->id;
			}

			// save images
			$chapterFolder = $coursesFolder."/$course_id/$id";
			if (!file_exists($chapterFolder))
				@mkdir($chapterFolder);

			if (file_exists($chapterFolder))
			{
				$connection->query("DELETE FROM _escuela_images WHERE chapter = '$id';");

				foreach($images as $idimg => $img)
				{
					file_put_contents($chapterFolder."/$idimg{$imgExt}", base64_decode($img['content']));
					$connection->query("INSERT INTO _escuela_images (id, filename, mime_type, chapter, course) VALUES ('$idimg','{$img['filename']}','{$img['type']}','$id','$course_id');");
				}
			}

			$this->view->chapter_id = $id;
			return $this->dispatcher->forward(array("controller"=> "school", "action" => "schoolChapter"));
		}
	}

	public function schoolEditChapterAction()
	{
		$url = $_GET['_url'];
		$id =  explode("/", $url);
		$id = $id[count($id) - 1];

		$connection = new Connection();
		$utils = new Utils();
		$this->view->message = false;
		$this->view->message_type = 'success';
		$this->view->title = "Edit chapter";

		$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '$id';");

		if (isset($r[0]))
		{
			$chapter = $r[0];
			$images = $this->getChapterImages($id);
			$chapter->content = $utils->putInlineImagesToHTML($chapter->content, $images, 'cid:', '.jpg');
			$this->view->chapter = $chapter;
			$this->view->setLayout('manage');
		}
		else
		{
			$this->dispatcher->forward(array("controller"=> "school", "action" => "pageNotFound"));
		}
	}

	public function schoolChapterAction()
	{
		if (isset($this->view->chapter_id))
		{
			$id =  $this->view->chapter_id;
		}
		else
		{
			$url = $_GET['_url'];
			$id =  explode("/",$url);
			$id = $id[count($id)-1];
		}

		$connection = new Connection();
		$utils = new Utils();

		$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '$id';");
		$chapter = $r[0];

		$images = $this->getChapterImages($id);
		$chapter->content = $utils->putInlineImagesToHTML($chapter->content, $images, 'cid:', '.jpg');

		$this->view->message = "The chapter <i>{$chapter->title}</i> was successful inserted";
		$this->view->message_type = 'success';
		$this->view->chapter = $chapter;
		$this->view->title = ($chapter->xtype=='CAPITULO'? "Chapter" : "Test") . ": {$chapter->title}";
		$this->view->setLayout('manage');
	}

	/**
	 * Manage test's questions and answers
	 *
	 * @author kuma
	 */
	public function schoolQuestionsAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';

		$chapter = intval($this->request->get('chapter'));
		$r = $connection->query("SELECT * FROM _escuela_course WHERE _escuela_course.id = (SELECT course FROM _escuela_chapter WHERE _escuela_chapter.id = '$chapter');");
		$course = $r[0];
		$course_id = $course->id;

		$this->view->course = $course;
		$option = $this->request->get('option');
		$sql = false;

		if ($this->request->isPost()){

			switch($option){
				case "addQuestion":
						$chapter = $this->request->getPost('chapter');
						$title = $this->request->getPost('chapterQuestionTitle');
						$r = $connection->query("SELECT max(xorder) as m FROM _escuela_question WHERE chapter = '$chapter';");
						$order = $r[0]->m + 1;
						$sql ="INSERT INTO _escuela_question (course, chapter, title, xorder) VALUES ('$course_id', '$chapter', '$title', '$order');";
						$this->view->message = "Question <b>$title</b> was inserted successfull";
				break;
				case "setQuestion":
						$question_id = $this->request->get('id');
						$title = $this->request->getPost('chapterQuestionTitle');
						$answer = $this->request->getPost('answer');
						$sql = "UPDATE _escuela_question SET title = '$title', answer = $answer WHERE id = '$question_id';";
						$this->view->message = "Question <b>$title</b> was updated successfull";
						break;
				case "addAnswer":
						$question_id = $this->request->get('question');
						$title = $this->request->getPost('chapterAnswerTitle');
						$sql ="INSERT INTO _escuela_answer (course, chapter, question, title) VALUES ('$course_id', '$chapter', '$question_id', '$title');";
						$this->view->message = "Answer <b>$title</b> was inserted successfull";
				break;
				case "setAnswer":
						$answer_id = $this->request->get('id');
						$title = $this->request->getPost('chapterAnswerTitle');
						$sql = "UPDATE _escuela_answer SET title = '$title' WHERE id = '$answer_id';";
						$this->view->message = "The answer was updated successfull";
				break;
			}
		}

		switch($option)
		{
			case "delAnswer":
				$answer_id = $this->request->get('id');
				$sql = "DELETE FROM _escuela_answer WHERE id ='{$answer_id}'";
				$this->view->message = "The answer was deleted successfull";
			break;

			case "delQuestion":
				$question_id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _escuela_question WHERE id = '{$question_id}';
						DELETE FROM _escuela_answer WHERE question ='{$question_id}';
						COMMIT;";
				$this->view->message = "The question was deleted successfull";
			break;
		}

		if ($sql != false) $connection->query($sql);

		$chapter = $this->request->get('chapter');

		$r = $connection->query("SELECT * FROM _escuela_chapter WHERE id = '{$chapter};'");
		if ($r !== false) {
			$sql = "SELECT * FROM _escuela_question WHERE chapter = '$chapter' order by xorder;";
			$chapter = $r[0];
			$questions = $connection->query($sql);
			if ($questions !== false)
			{
				foreach ($questions as $k=>$q){
					$answers = $connection->query("SELECT * FROM _escuela_answer WHERE question = '{$q->id}';");
					if ($answers==false) $answers = array();
					$questions[$k]->answers=$answers;
				}

				$this->view->title = "Test: ".$chapter->title;
				$this->view->chapter = $chapter;
				$this->view->questions = $questions;
			}
		}

		$this->view->setLayout('manage');
	}

	private function getChapterImages($chapter_id)
	{
		$connection = new Connection();
		$r = $connection->query("SELECT * FROM _escuela_images WHERE chapter = '$chapter_id';");

		$wwwroot = $this->di->get('path')['root'];
		$images = [];

		if ($r !== false)
		{
			foreach ($r as $row)
			{   $imageContent = file_get_contents($wwwroot."/public/courses/{$row->course}/$row->chapter/{$row->id}.jpg");
				$images[$row->id] = ['filename' => $row->filename, 'type' => $row->mime_type, 'content' => base64_encode($imageContent)];
			}
		}
		return $images;
	}
}
