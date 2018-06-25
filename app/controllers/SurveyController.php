<?php

use Phalcon\Mvc\Controller;

class SurveyController extends Controller
{
	// do not let anonymous users pass
	public function initialize()
	{
		$security = new Security();
		$security->enforceLogin();
		$this->view->setLayout("manage");
	}

	/**
	 * List of surveys
	 *
	 * @author kuma
	 */
	public function surveysAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$option = $this->request->get('option');
		$sql = false;

		// if dats is passed, run a query
		switch ($option)
		{
			case 'addSurvey':
				$customer = $this->request->getPost("surveyCustomer");
				$title = $this->request->getPost("surveyTitle");
				$deadline = $this->request->getPost("surveyDeadline");
				$sql = "INSERT INTO _survey (customer, title, deadline, details) VALUES ('$customer', '$title', '$deadline', ''); ";
				$this->view->message = 'The survey was inserted successfull';
				break;

			case 'setSurvey':
				$customer = $this->request->getPost("surveyCustomer");
				$title = $this->request->getPost("surveyTitle");
				$deadline = $this->request->getPost("surveyDeadline");
				$id = $this->request->get('id');
				$sql = "UPDATE _survey SET customer = '$customer', title = '$title', deadline = '$deadline' WHERE id = '$id'; ";
				$this->view->message = 'The survey was updated successfull';
				break;

			case "delSurvey":
				$id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _survey_answer WHERE question = (SELECT id FROM _survey_question WHERE _survey_question.survey = '$id');
						DELETE FROM _survey_question WHERE survey = '$id';
						DELETE FROM _survey WHERE id = '$id';
						COMMIT;";
				$this->view->message = 'The survey was deleted successfully';
				break;

			case "disable":
				$id = $this->request->get('id');
				$sql = "UPDATE _survey SET active = 0 WHERE id ='$id';";
				break;

			case "enable":
				$id = $this->request->get('id');
				$sql = "UPDATE _survey SET active = 1 WHERE id ='$id';";
				break;
		}

		// commit SQL if exist
		if ($sql) $connection->query($sql);

		// get all surveys
		$surveys = $connection->query("SELECT * FROM _survey ORDER BY ID");

		// send variables to the view
		$this->view->title = "List of surveys (".count($surveys).")";
		$this->view->surveys = $surveys;
	}

	/**
	 * Manage survey's questions and answers
	 *
	 * @author kuma
	 */
	public function surveyQuestionsAction()
	{
		$connection = new Connection();
		$this->view->message = false;
		$this->view->message_type = 'success';
		$this->view->buttons = [["caption"=>"Back", "href"=>"/survey/surveys"]];

		$option = $this->request->get('option');
		$sql = false;
		if ($this->request->isPost()){

			switch($option){
				case "addQuestion":
					$survey = $this->request->getPost('survey');
					$title = $this->request->getPost('surveyQuestionTitle');
					$sql ="INSERT INTO _survey_question (survey, title) VALUES ('$survey','$title');";
					$this->view->message = "Question <b>$title</b> was inserted successfull";
				break;
				case "setQuestion":
					$question_id = $this->request->get('id');
					$title = $this->request->getPost('surveyQuestionTitle');
					$sql ="UPDATE _survey_question SET title = '$title' WHERE id = '$question_id';";
					$this->view->message = "Question <b>$title</b> was updated successfull";
					break;
				case "addAnswer":
					$question_id = $this->request->get('question');
					$title = $this->request->getPost('surveyAnswerTitle');
					$sql ="INSERT INTO _survey_answer (question, title) VALUES ('$question_id','$title');";
					$this->view->message = "Answer <b>$title</b> was inserted successfull";
				break;
				case "setAnswer":
					$answer_id = $this->request->get('id');
					$title = $this->request->getPost('surveyAnswerTitle');
					$sql = "UPDATE _survey_answer SET title = '$title' WHERE id = '$answer_id';";
					$this->view->message = "The answer was updated successfull";
				break;
			}
		}

		switch($option)
		{
			case "delAnswer":
				$answer_id = $this->request->get('id');
				$sql = "DELETE FROM _survey_answer WHERE id ='{$answer_id}'";
				$this->view->message = "The answer was deleted successfull";
			break;

			case "delQuestion":
				$question_id = $this->request->get('id');
				$sql = "START TRANSACTION;
						DELETE FROM _survey_question WHERE id = '{$question_id}';
						DELETE FROM _survey_answer WHERE question ='{$question_id}';
						COMMIT;";
				$this->view->message = "The question was deleted successfull";
			break;
		}

		if ($sql!=false) $connection->query($sql);

		$survey = $this->request->get('survey');

		$r = $connection->query("SELECT * FROM _survey WHERE id = '{$survey};'");
		if ($r !== false) {
			$sql = "SELECT * FROM _survey_question WHERE survey = '$survey' order by id;";
			$survey = $r[0];
			$questions = $connection->query($sql);
			if ($questions !== false) {

				foreach ($questions as $k=>$q){
					$answers = $connection->query("SELECT * FROM _survey_answer WHERE question = '{$q->id}';");
					if ($answers==false) $answers = array();
					$questions[$k]->answers=$answers;
				}

				$this->view->title = $survey->title;
				$this->view->survey = $survey;
				$this->view->questions = $questions;
			}
		}
	}

	/**
	 * Survey reports
	 */
	public function surveyReportAction(){
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);

		// get the report
		$report = $this->getSurveyResults($id);

		// get the survey
		$connection = new Connection();
		$survey = $connection->query("SELECT * FROM _survey WHERE id = $id")[0];

		// get codes for each province
		$trans = array(
			'LA HABANA' => 'CH',
			'PINAR DEL RIO' => 'PR',
			'MATANZAS' => 'MA',
			'MAYABEQUE' => 'MY',
			'ARTEMISA' => 'AR',
			'VILLA CLARA' => 'VC',
			'SANCTI SPIRITUS' => 'SS',
			'CIEGO DE AVILA' => 'CA',
			'CIENFUEGOS' => 'CF',
			'CAMAGUEY' => 'CM',
			'LAS TUNAS' => 'LT',
			'HOLGUIN' => 'HL',
			'GRANMA' => 'GR',
			'SANTIAGO DE CUBA' => 'SC',
			'GUANTANAMO' => 'GU',
			'ISLA DE LA JUVENTUD' => 'IJ'
		);

		// add buttons to the header
		$this->view->buttons = [
			["caption"=>"PDF", "href"=>"/survey/surveyReportPDF/{$survey->id}", "icon"=>"cloud-download"],
			["caption"=>"CSV", "href"=>"/survey/surveyResultsCSV/{$survey->id}", "icon"=>"cloud-download"],
			["caption"=>"Back", "href"=>"/survey/surveys"]
		];

		// send data to the view
		$this->view->results = $report;
		$this->view->survey = $survey;
		$this->view->title = $survey->title;
		$this->view->trans = $trans;
	}

	/**
	 * Calculate and return survey's results
	 * @author kuma
	 * @param integer $id
	 */
	private function getSurveyResults($id){
		$connection = new Connection();
		$survey = $connection->query("SELECT * FROM _survey WHERE id = $id;");

		$by_age = array(
				'0-16' => 0,
				'17-21' => 0,
				'22-35' => 0,
				'36-55' => 0,
				'56-130' => 0
		);

		if ($survey !== false){

			$enums = array(
					'person.age' => 'By age',
					'person.province' => "By location",
					'person.gender' => 'By gender',
					'person.highest_school_level' => 'By level of education',
					'person.skin' => 'By skin'
			);

			$report = array();

			foreach ($enums as $field => $enum_label){
				$sql = "
				SELECT
				_survey.id AS survey_id,
				_survey.title AS survey_title,
				_survey_question.id AS question_id,
				_survey_question.title AS question_title,
				_survey_answer.id AS answer_id,
				_survey_answer.title AS answer_title,
				IFNULL($field,'_UNKNOW') AS pivote,
				Count(_survey_answer_choosen.email) AS total
				FROM
				_survey Inner Join (_survey_question inner join ( _survey_answer inner join (_survey_answer_choosen inner join (select *, YEAR(CURDATE()) - YEAR(person.date_of_birth) as age from person) as person ON _survey_answer_choosen.email = person.email) on _survey_answer_choosen.answer = _survey_answer.id) ON _survey_question.id = _survey_answer.question)
				ON _survey_question.survey = _survey.id
				WHERE _survey.id = $id
				GROUP BY
				_survey.id,
				_survey.title,
				_survey_question.id,
				_survey_question.title,
				_survey_answer.id,
				_survey_answer.title,
				$field
				ORDER BY _survey.id, _survey_question.id, _survey_answer.id, pivote";

				$r = $connection->query($sql);

				$pivots = array();
				$totals = array();
				$results = array();
				if ($r!==false){
					foreach($r as $item){
						$item->total = intval($item->total);
						$q = intval($item->question_id);
						$a = intval($item->answer_id);
						if (!isset($results[$q]))
							$results[$q] = array(
									"i" => $q,
									"t" => $item->question_title,
									"a" => array(),
									"total" => 0
							);

							if (!isset($results[$q]['a'][$a]))
								$results[$q]['a'][$a] = array(
										"i" => $a,
										"t" => $item->answer_title,
										"p" => array(),
										"total" => 0
								);

								$pivot = $item->pivote;

								if ($field == 'person.age'){
									if (trim($pivot)=='' || $pivot=='0' || $pivot =='NULL') $pivot='_UNKNOW';
									elseif ($pivot*1 < 17) $pivot = '0-16';
									elseif ($pivot*1 > 16 && $pivot*1 < 22) $pivot = '17-21';
									elseif ($pivot*1 > 21 && $pivot*1 < 36) $pivot = '22-35';
									elseif ($pivot*1 > 35 && $pivot*1 < 56) $pivot = '36-55';
									elseif ($pivot*1 > 55) $pivot = '56-130';
								}

								$results[$q]['a'][$a]['p'][$pivot] = $item->total;

								if (!isset($totals[$a]))
									$totals[$a] = 0;

									$totals[$a] += $item->total;
									$results[$q]['a'][$a]['total'] += $item->total;
									$results[$q]['total'] += $item->total;
									$pivots[$pivot] = str_replace("_"," ", $pivot);
					}
				}

				// fill details...
				$sql = "
				SELECT
				_survey.id AS survey_id,
				_survey.title AS survey_title,
				_survey_question.id AS question_id,
				_survey_question.title AS question_title,
				_survey_answer.id AS answer_id,
				_survey_answer.title AS answer_title
				FROM
				_survey Inner Join (_survey_question inner join
				_survey_answer ON _survey_question.id = _survey_answer.question)
				ON _survey_question.survey = _survey.id
				WHERE _survey.id = $id
				ORDER BY _survey.id, _survey_question.id, _survey_answer.id";

				$survey_details = $connection->query($sql);

				foreach($survey_details as $item){
					$q = intval($item->question_id);
					$a = intval($item->answer_id);
					if (!isset($results[$q]))
						$results[$q] = array(
								"i" => $q,
								"t" => $item->question_title,
								"a" => array()
						);

						if (!isset($results[$q]['a'][$a]))
							$results[$q]['a'][$a] = array(
									"i" => $a,
									"t" => $item->answer_title,
									"p" => array(),
									"total" => 0
							);
							if (!isset($totals[$a]))
								$totals[$a] = 0;
				}



				asort($pivots);
				unset($pivots['_UNKNOW']);
				$pivots['_UNKNOW'] = 'UNKNOW';

				$report[$field] = array(
						'label' => $enum_label,
						'results' => $results,
						'pivots' => $pivots,
						'totals' => $totals
				);

				// adding unknow labels

				foreach ($report[$field]['results'] as $k => $question){
					foreach($question['a'] as $kk => $ans){
						$report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] = $totals[$ans['i']*1];
						foreach($ans['p'] as $kkk => $pivot){
							if ($kkk!="_UNKNOW") {
							$report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] -= $pivot;
						}
					}
				}
			}
		}
			return $report;
		}

		return false;
	}

	/**
	 * Download survey's results as CSV
	 * @author kuma
	 */
	public function surveyResultsCSVAction()
	{
		// getting ad's id
		// @TODO: improve this!
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);
		$connection = new Connection();
		$survey = $connection->query("SELECT * FROM _survey WHERE id = $id;");
		$survey = $survey[0];
		$results = $this->getSurveyResults($id);
		$csv = array();

		$csv[0][0] = $survey->title;
		$csv[1][0] = "";

		 foreach ($results as $field => $result){

			$csv[][0] = $result['label'];
			$row = array('','Total','Percentage');

			foreach ($result['pivots'] as $pivot => $label)
				$row[] = $label;

			$csv[] = $row;

			foreach($result['results'] as $question){
				$csv[][0] = $question['t'];
				foreach($question['a'] as $ans) {

					if (!isset($ans['total'])) $ans['total'] = 0;
					if (!isset($question['total'])) $question['total'] = 0;

					$row = array($ans['t'], $ans['total'], ($question['total'] ===0?0:number_format($ans['total'] / $question['total'] * 100, 1)));
					foreach ($result['pivots'] as $pivot => $label) {
						if (!isset($ans['p'][$pivot])) {
							$row[] = "0.0";
						} else {
							$part = intval($ans['p'][$pivot]);
							$total = intval($ans['total']);
							$percent = $total === 0?0:$part/$total*100;
							$row[] = number_format($percent,1);
						}
					}
					$csv[] = $row;
				}
				$csv[][0] = '';
			}
			$csv[][0] = '';
		 }

		$csvtext = '';
		foreach($csv as $i => $row){
			foreach ($row as $j => $cell){
				$csvtext .= '"'.$cell.'";';
			}
			$csvtext .="\n";
		}

		header("Content-type: text/csv");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=\"ap-survey-$id-results-".date("Y-m-d-h-i-s").".csv\"");
		header("Content-Length: ".strlen($csvtext));
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Accept-Ranges: bytes");

		echo $csvtext;

		$this->view->disable();
	}

	/**
	 * Download survey's results as PDF
	 * @author kuma
	 */
	public function surveyReportPDFAction()
	{
		// getting ad's id
		$url = $_GET['_url'];
		$id =  explode("/",$url);
		$id = intval($id[count($id)-1]);

		$connection = new Connection();
		$survey = $connection->query("SELECT * FROM _survey WHERE id = $id");
		$survey = $survey[0];

 		$csv = array();
 		$title = "{$survey->title} - ".date("d M, Y");

 		$html = '<html><head><title>'.$title.'</title><style>
 				h1 {color: #5EBB47;text-decoration: underline;font-size: 24px; margin-top: 0px;}
 				h2{ color: #5EBB47; font-size: 16px; margin-top: 0px; }
 				body{font-family:Verdana;}</style><body>';
 		$html .= "<br/><h1>$title</h1>";

		$questions = $connection->query("SELECT * FROM _survey_question WHERE survey = $id;");

		$i = 0;
		$total = count($questions);
		foreach($questions as $question)
		{
			//$html .= "<h2>". $question->title . "</h2>";
			$answers = $connection->query("SELECT *, (SELECT count(_survey_answer_choosen.email) FROM _survey_answer_choosen WHERE _survey_answer_choosen.answer = _survey_answer.id) as choosen FROM _survey_answer WHERE question = {$question->id};");

			$values = '';
			foreach($answers as $ans) {
				$values[wordwrap($ans->title,50)." ({$ans->choosen})"] = $ans->choosen;
			}

			$PieChart = null;
			$chart = $this->getPieChart($question->title, $values, $PieChart);

			$html .= '<table width="100%"><tr><td valign="top" width="250">';
			$html .= '<thead><caption>'.$question->title.'</caption></thead>';
			$html .= '<img src="data:image/png;base64,'.$chart.'"><br/>';
			$html .="</td><td valign=\"top\">";

			$Data	= $PieChart->pDataObject->getData();
			$Palette = $PieChart->pDataObject->getPalette();

			$html .= "<table width=\"100%\">";
			foreach($Data["Series"][$Data["Abscissa"]]["Data"] as $Key => $Value)
			{
				$R = $Palette[$Key]["R"];
				$G = $Palette[$Key]["G"];
				$B = $Palette[$Key]["B"];
				$html .= "<tr><td>";
				$html .= "<tr><td><span style=\"width:30px;height:30px;background:rgb($R,$G,$B);\">&nbsp;&nbsp;</span></td><td>$Value</td></tr>";
			}
			$html .= "</table>";
			$html .= "</td></tr></table><br/>";

			$i++;
			//if ($i % 4 == 0 && $i < $total) $html .= '<pagebreak />';
		}
 		$html .= '</body></html>';

		// save the PDF and download
		$wwwroot = $this->di->get('path')['root'];

		if (!class_exists('mPDF'))
			include_once $wwwroot."/lib/mpdf/mpdf.php";

		$mpdf = new Mpdf();
		$mpdf->WriteHTML(trim($html));
		$mpdf->Output("$title.pdf", 'D');
		$this->view->disable();
	}

	/**
	 * Get image with a pie chart
	 *
	 * @author kuma
	 * @param string $title
	 * @param array $values
	 */
	private function getPieChart($title, $values, &$chartObj){

		include_once "../lib/pChart2.1.4/class/pData.class.php";
		include_once "../lib/pChart2.1.4/class/pDraw.class.php";
		include_once "../lib/pChart2.1.4/class/pPie.class.php";
		include_once "../lib/pChart2.1.4/class/pImage.class.php";

		$MyData = new pData();
		$MyData->addPoints($values,"ScoreA");
		$MyData->setSerieDescription("ScoreA",$title);
		$MyData->addPoints(array_keys($values),"Labels");
		$MyData->setAbscissa("Labels");

		$myPicture = new pImage(250,150,$MyData);
		$myPicture->setFontProperties(array(
			"FontName" => "../lib/pChart2.1.4/fonts/verdana.ttf",
			"FontSize" => 13, "R" => 0, "G" => 0, "B" => 0));

		$myPicture->drawText(10, 23, $title, array(
			"R" => 255,
			"G" => 255,
			"B" => 255
		));

		$myPicture->setShadow(TRUE, array(
			"X" => 2,
			"Y" => 2,
			"R" => 0,
			"G" => 0,
			"B" => 0,
			"Alpha" => 50
		));

		$PieChart = new pPie($myPicture,$MyData);
		$PieChart->draw2DPie(125, 80, array(
			"Radius" => 50,
			"WriteValues" => PIE_VALUE_PERCENTAGE,
			"ValuePadding" => 10,
			"DataGapAngle" => 0,
			"DataGapRadius" => 0,
			"Border" => FALSE,
			"BorderR" => 0,
			"BorderG" => 0,
			"BorderB"=> 0,
			"ValueR"=> 0,
			"ValueG" => 0,
			"ValueB" => 0,
			"Shadow" => FALSE
		));

		$chartObj = $PieChart;

		ob_start();
		imagepng($myPicture->Picture);
		$img = ob_get_contents();
		ob_end_clean();

		return base64_encode($img);
	}
}
