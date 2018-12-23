<?php

use Phalcon\Mvc\Controller;

class SurveyController extends Controller {

  // do not let anonymous users pass
  public function initialize() {
    $security = new Security();
    $security->enforceLogin();
    $this->view->setLayout("manage");
  }

  public function indexAction() {
    return $this->response->redirect('survey/surveys');
  }

  /**
   * List of surveys
   *
   * @author kumahacker
   */
  public function surveysAction() {
    $this->view->setVar('message', FALSE);
    $option = $this->request->get('option');
    $sql    = FALSE;

    switch ($option) {
      case 'addSurvey':
        $customer = $this->request->getPost("surveyCustomer");
        $title    = $this->request->getPost("surveyTitle");
        $deadline = $this->request->getPost("surveyDeadline");
        $sql      = "INSERT INTO _survey (customer, title, deadline, details) VALUES ('$customer', '$title', '$deadline', ''); ";
        $this->view->setVar('message', 'The survey was inserted successfull');
        break;

      case 'setSurvey':
        $customer = $this->request->getPost("surveyCustomer");
        $title    = $this->request->getPost("surveyTitle");
        $deadline = $this->request->getPost("surveyDeadline");
        $id       = $this->request->get('id');
        $sql      = "UPDATE _survey SET customer = '$customer', title = '$title', deadline = '$deadline' WHERE id = '$id'; ";
        $this->view->setVar('message', 'The survey was updated successfull');
        break;

      case "delSurvey":
        $id  = $this->request->get('id');
        $sql = "START TRANSACTION;
						DELETE FROM _survey_answer WHERE question = (SELECT id FROM _survey_question WHERE _survey_question.survey = '$id');
						DELETE FROM _survey_question WHERE survey = '$id';
						DELETE FROM _survey WHERE id = '$id';
						COMMIT;";
        $this->view->setVar('message', 'The survey was deleted successfully');
        break;

      case "disable":
        $id  = $this->request->get('id');
        $sql = "UPDATE _survey SET active = 0 WHERE id ='$id';";
        break;

      case "enable":
        $id  = $this->request->get('id');
        $sql = "UPDATE _survey SET active = 1 WHERE id ='$id';";
        break;
    }

    // commit SQL if exist
    if ($sql) {
      Connection::query($sql);
    }

    // get all surveys
    $surveys = Connection::query("SELECT * FROM _survey ORDER BY id;");

    // send variables to the view
    $this->view->setVars([
      'title'   => "List of surveys (" . count($surveys) . ")",
      'surveys' => $surveys,
    ]);
  }

  /**
   * Manage survey's questions and answers
   *
   * @author kumahacker
   */
  public function surveyQuestionsAction() {
    $this->view->setVar('message', FALSE);
    $this->view->setVar('message_type', 'success');
    $this->view->setVar('buttons', [
      [
        "caption" => "Back",
        "href"    => "/survey/surveys",
      ],
    ]);

    $option = $this->request->get('option');
    $sql    = FALSE;
    if ($this->request->isPost()) {

      switch ($option) {
        case "addQuestion":
          $survey = $this->request->getPost('survey');
          $title  = $this->request->getPost('surveyQuestionTitle');
          $sql    = "INSERT INTO _survey_question (survey, title) VALUES ('$survey','$title');";
          $this->view->setVar('message', "Question <b>$title</b> was inserted successfull");
          break;
        case "setQuestion":
          $question_id = $this->request->get('id');
          $title       = $this->request->getPost('surveyQuestionTitle');
          $sql         = "UPDATE _survey_question SET title = '$title' WHERE id = '$question_id';";
          $this->view->setVar('message', "Question <b>$title</b> was updated successfull");
          break;
        case "addAnswer":
          $question_id = $this->request->get('question');
          $title       = $this->request->getPost('surveyAnswerTitle');
          $sql         = "INSERT INTO _survey_answer (question, title) VALUES ('$question_id','$title');";
          $this->view->setVar('message', "Answer <b>$title</b> was inserted successfull");
          break;
        case "setAnswer":
          $answer_id = $this->request->get('id');
          $title     = $this->request->getPost('surveyAnswerTitle');
          $sql       = "UPDATE _survey_answer SET title = '$title' WHERE id = '$answer_id';";
          $this->view->setVar('message', "The answer was updated successful");
          break;
      }
    }

    switch ($option) {
      case "delAnswer":
        $answer_id = $this->request->get('id');
        $sql       = "DELETE FROM _survey_answer WHERE id ='{$answer_id}'";
        $this->view->setVar('message', "The answer was deleted successful");
        break;

      case "delQuestion":
        $question_id = $this->request->get('id');
        $sql         = "START TRANSACTION;
						DELETE FROM _survey_question WHERE id = '{$question_id}';
						DELETE FROM _survey_answer WHERE question ='{$question_id}';
						COMMIT;";
        $this->view->setVar('message', "The question was deleted successful");
        break;
    }

    if ($sql != FALSE) {
      Connection::query($sql);
    }

    $id     = $this->request->get('survey');
    $survey = self::getSurvey($id);

    if ($survey !== FALSE) {
      $sql       = "SELECT * FROM _survey_question WHERE survey = '$id' order by id;";
      $questions = Connection::query($sql);
      if ($questions !== FALSE) {

        foreach ($questions as $k => $q) {
          $answers = Connection::query("SELECT * FROM _survey_answer WHERE question = '{$q->id}';");
          if ($answers == FALSE) {
            $answers = [];
          }
          $questions[$k]->answers = $answers;
        }

        $this->view->setVars([
          'title'     => $survey->title,
          'survey'    => $survey,
          'questions' => $questions,
        ]);

      }
    }
  }

  /**
   * @param $id
   *
   * @throws \Phalcon\Exception
   * @return mixed
   */
  public static function getSurvey($id) {
    $survey = Connection::query("SELECT * FROM _survey where id = $id;");

    if (empty($survey)) {
      throw new Phalcon\Exception("Survey $id not found");
    }
    return $survey[0];
  }

  /**
   * Survey reports
   */
  public function surveyReportAction() {
    $id     = intval($_GET['id']);
    $report = $this->getSurveyResults($id);
    $survey = self::getSurvey($id);

    // send data to the view
    $this->view->setVars([
      // add buttons to the header
      'buttons' => [
        [
          "caption" => "PDF",
          "href"    => "/survey/surveyReportPDF?id={$id}",
          "icon"    => "cloud-download",
        ],
        [
          "caption" => "CSV",
          "href"    => "/survey/surveyResultsCSV?id={$id}",
          "icon"    => "cloud-download",
        ],
        [
          "caption" => "CSV By User",
          "href"    => "/survey/surveyResultsCSVByUser?id={$id}",
          "icon"    => "cloud-download",
        ],
        ["caption" => "Back", "href" => "/survey/surveys"],
      ],
      'results' => $report,
      'survey'  => $survey,
      'title'   => $survey->title,
      // get codes for each province
      'trans'   => [
        'LA HABANA'           => 'CH',
        'PINAR DEL RIO'       => 'PR',
        'MATANZAS'            => 'MA',
        'MAYABEQUE'           => 'MY',
        'ARTEMISA'            => 'AR',
        'VILLA CLARA'         => 'VC',
        'SANCTI SPIRITUS'     => 'SS',
        'CIEGO DE AVILA'      => 'CA',
        'CIENFUEGOS'          => 'CF',
        'CAMAGUEY'            => 'CM',
        'LAS TUNAS'           => 'LT',
        'HOLGUIN'             => 'HL',
        'GRANMA'              => 'GR',
        'SANTIAGO DE CUBA'    => 'SC',
        'GUANTANAMO'          => 'GU',
        'ISLA DE LA JUVENTUD' => 'IJ',
      ],
    ]);
  }

  /**
   * Calculate and return survey's results
   *
   * @author kumahacker
   *
   * @param integer $id
   *
   * @return array | boolean
   */
  private function getSurveyResults($id) {
    $survey  = self::getSurvey($id);
    $exclude = $this->excludedUsers($id);

    if ($survey !== FALSE) {

      $enums = [
        'person.date_of_birth'        => 'By age',
        'person.province'             => "By location",
        'person.gender'               => 'By gender',
        'person.highest_school_level' => 'By level of education',
        'person.skin'                 => 'By skin',
      ];

      $report = [];

      foreach ($enums as $field => $enum_label) {
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
				WHERE _survey.id = $id AND NOT _survey_answer_choosen.email IN($exclude) 
				GROUP BY
				_survey.id,
				_survey.title,
				_survey_question.id,
				_survey_question.title,
				_survey_answer.id,
				_survey_answer.title,
				$field
				ORDER BY _survey.id, _survey_question.id, _survey_answer.id, pivote";

        $r = Connection::query($sql);

        $pivots  = [];
        $totals  = [];
        $results = [];
        if ($r !== FALSE) {
          foreach ($r as $item) {
            $item->total = intval($item->total);
            $q           = intval($item->question_id);
            $a           = intval($item->answer_id);
            if (!isset($results[$q])) {
              $results[$q] = [
                "i"     => $q,
                "t"     => $item->question_title,
                "a"     => [],
                "total" => 0,
              ];
            }

            if (!isset($results[$q]['a'][$a])) {
              $results[$q]['a'][$a] = [
                "i"     => $a,
                "t"     => $item->answer_title,
                "p"     => [],
                "total" => 0,
              ];
            }

            $pivot = $item->pivote;

            if ($field == 'person.date_of_birth' and !isset($results[$q]['a'][$a]['p']['_UNKNOW'])) {
              $results[$q]['a'][$a]['p']['0-16']    = 0;
              $results[$q]['a'][$a]['p']['17-21']   = 0;
              $results[$q]['a'][$a]['p']['22-35']   = 0;
              $results[$q]['a'][$a]['p']['36-55']   = 0;
              $results[$q]['a'][$a]['p']['56-130']  = 0;
              $results[$q]['a'][$a]['p']['_UNKNOW'] = 0;

              $pivots['0-16']    = '0-16';
              $pivots['17-21']   = '17-21';
              $pivots['22-35']   = '22-35';
              $pivots['36-55']   = '36-55';
              $pivots['56-130']  = '56-130';
              $pivots['_UNKNOW'] = 'UNKNOW';
            }

            if ($field == 'person.date_of_birth') {
              $pivot = empty($pivot) ? 0 : intval(date_diff(date_create($pivot), date_create('today'))->y);
              if ($pivot <= 0) {
                $results[$q]['a'][$a]['p']['_UNKNOW'] += $item->total;
              }
              elseif ($pivot < 17) {
                $results[$q]['a'][$a]['p']['0-16'] += $item->total;
              }
              elseif ($pivot > 16 && $pivot < 22) {
                $results[$q]['a'][$a]['p']['17-21'] += $item->total;
              }
              elseif ($pivot > 21 && $pivot < 36) {
                $results[$q]['a'][$a]['p']['22-35'] += $item->total;
              }
              elseif ($pivot > 35 && $pivot < 56) {
                $results[$q]['a'][$a]['p']['36-55'] += $item->total;
              }
              elseif ($pivot > 55) {
                $results[$q]['a'][$a]['p']['56-130'] += $item->total;
              }
            }
            if (!isset($totals[$a])) {
              $totals[$a] = 0;
            }

            $totals[$a]                    += $item->total;
            $results[$q]['a'][$a]['total'] += $item->total;
            $results[$q]['total']          += $item->total;
            if ($field != 'person.date_of_birth') {
              $results[$q]['a'][$a]['p'][$pivot] = $item->total;
              $pivots[$pivot]                    = str_replace("_", " ", $pivot);
            }
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

        $survey_details = Connection::query($sql);

        foreach ($survey_details as $item) {
          $q = intval($item->question_id);
          $a = intval($item->answer_id);
          if (!isset($results[$q])) {
            $results[$q] = [
              "i" => $q,
              "t" => $item->question_title,
              "a" => [],
            ];
          }

          if (!isset($results[$q]['a'][$a])) {
            $results[$q]['a'][$a] = [
              "i"     => $a,
              "t"     => $item->answer_title,
              "p"     => [],
              "total" => 0,
            ];
          }
          if (!isset($totals[$a])) {
            $totals[$a] = 0;
          }
        }

        asort($pivots);
        unset($pivots['_UNKNOW']);
        $pivots['_UNKNOW'] = 'UNKNOW';

        $report[$field] = [
          'label'   => $enum_label,
          'results' => $results,
          'pivots'  => $pivots,
          'totals'  => $totals,
        ];

        // adding unknow labels
        foreach ($report[$field]['results'] as $k => $question) {
          foreach ($question['a'] as $kk => $ans) {
            $report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] = $totals[$ans['i'] * 1];
            foreach ($ans['p'] as $kkk => $pivot) {
              if ($kkk != "_UNKNOW") {
                $report[$field]['results'][$k]['a'][$kk]['p']['_UNKNOW'] -= $pivot;
              }
            }
          }
        }
      }
      return $report;
    }

    return FALSE;
  }

  /**
   * Exclude the users that not finished the survey
   *
   * @author ricardo@apretaste.org
   *
   * @param Int $id
   *
   * @return String $exclude
   */
  private function excludedUsers($id) {
    $unfinished = Connection::query(
      "SELECT email, choosen FROM (
			SELECT email, COUNT(question) as choosen 
			FROM _survey_answer_choosen 
			WHERE survey = $id GROUP BY email) A 
			WHERE choosen < (SELECT COUNT(id) 
			FROM _survey_question 
			WHERE survey = $id)");

    $exclude = [];

    foreach ($unfinished as $u) {
      $exclude[] = "'{$u->email}'";
    }
    $exclude = implode(',', $exclude);

    return $exclude;
  }

  /**
   * Download survey's results as CSV
   *
   * @author kumahacker
   */
  public function surveyResultsCSVAction() {
    // getting ad's id
    $id      = intval($_GET['id']);
    $survey  = self::getSurvey($id);
    $results = $this->getSurveyResults($id);
    $csv     = [];

    $csv[0][0] = $survey->title;
    $csv[1][0] = "";

    foreach ($results as $field => $result) {

      $csv[][0] = $result['label'];
      $row      = ['', 'Total', 'Percentage'];

      foreach ($result['pivots'] as $pivot => $label) {
        $row[] = $label;
      }

      $csv[] = $row;

      foreach ($result['results'] as $question) {
        $csv[][0] = htmlspecialchars(html_entity_decode($question['t'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        foreach ($question['a'] as $ans) {

          if (!isset($ans['total'])) {
            $ans['total'] = 0;
          }
          if (!isset($question['total'])) {
            $question['total'] = 0;
          }

          $ans['t'] = htmlspecialchars(html_entity_decode($ans['t'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
          $row      = [
            $ans['t'],
            $ans['total'],
            ($question['total'] === 0 ? 0 : number_format($ans['total'] / $question['total'] * 100, 1)),
          ];
          foreach ($result['pivots'] as $pivot => $label) {
            if (!isset($ans['p'][$pivot])) {
              $row[] = "0.0";
            }
            else {
              $part    = intval($ans['p'][$pivot]);
              $total   = intval($ans['total']);
              $percent = $total === 0 ? 0 : $part / $total * 100;
              $row[]   = number_format($percent, 1);
            }
          }
          $csv[] = $row;
        }
        $csv[][0] = '';
      }
      $csv[][0] = '';
    }

    $csvtext = '';
    foreach ($csv as $i => $row) {
      foreach ($row as $j => $cell) {
        $csvtext .= '"' . $cell . '";';
      }
      $csvtext .= "\n";
    }

    header("Content-type: text/csv");
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=\"ap-survey-$id-results-" . date("Y-m-d-h-i-s") . ".csv\"");
    header("Content-Length: " . strlen($csvtext));
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Accept-Ranges: bytes");

    echo $csvtext;

    $this->view->disable();
  }

  /**
   * Download survey's results as CSV By User
   *
   * @author ricardo@apretaste.com
   */

  public function surveyResultsCSVByUserAction() {
    $id = intval($_GET['id']);

    //get the excluded users
    $exclude = $this->excludedUsers($id);

    $survey    = self::getSurvey($id);
    $questions = Connection::query("SELECT title FROM _survey_question WHERE survey=$id");

    $data = Connection::query("SELECT SUBSTR(A.email,1,INSTR(A.email,'@')-1) AS email, A.gender AS gender, A.province AS province,
			TIMESTAMPDIFF(YEAR,A.date_of_birth,NOW()) AS age, A.highest_school_level AS school_level, A.skin AS skin,
			C.title AS question, D.title AS answer
			FROM person A JOIN _survey_answer_choosen B
			JOIN _survey_question C
			JOIN _survey_answer D
			ON A.email=B.email AND C.id=D.question AND B.answer=D.id 
			WHERE B.survey=$id AND NOT B.email IN($exclude)");

    $answers = [];

    foreach ($data as $answer) {
      if (!array_key_exists($answer->email, $answers)) {
        $answers[$answer->email] = [];
        $answers[$answer->email] = [
          'gender'       => $answer->gender,
          'province'     => $answer->province,
          'age'          => $answer->age,
          'school_level' => $answer->school_level,
          'skin'         => $answer->skin,
          'answers'      => [],
        ];
      }
      if (!array_key_exists($answer->question, $answers[$answer->email]['answers'])) {
        $answers[$answer->email]['answers'][$answer->question] = ($answer->answer);
      }
      else {
        $num = 0;
        while (isset($answers[$answer->email . $num]) && array_key_exists($answer->question, $answers[$answer->email . $num]['answers'])) {
          $num++;
        }

        if (!array_key_exists($answer->email . $num, $answers)) {
          $answers[$answer->email . $num] = [];
          $answers[$answer->email . $num] = [
            'gender'       => $answer->gender,
            'province'     => $answer->province,
            'age'          => $answer->age,
            'school_level' => $answer->school_level,
            'skin'         => $answer->skin,
            'answers'      => [],
          ];
        }

        $answers[$answer->email . $num]['answers'][$answer->question] = ($answer->answer);
      }
    }

    foreach ($answers as $key => $person) {
      foreach ($questions as $question) {
        if (!array_key_exists($question->title, $person['answers'])) {
          $answers[$key]['answers'][$question->title] = "-";
        }
      }
      $aux                      = $answers[$key]['answers'];
      $answers[$key]['answers'] = [];

      foreach ($questions as $question) {
        $answers[$key]['answers'][$question->title] = $aux[$question->title];
      }
    }

    $headerRow = [
      'Usuario',
      'Genero',
      'Localizacion',
      'Edad',
      'Nivel Escolar',
      'Piel',
    ];
    foreach ($questions as $question) {
      $headerRow[] = $question->title;
    }

    $dataRows = [];
    foreach ($answers as $key => $person) {
      $row   = [];
      $row[] = $key;
      $row[] = $person['gender'];
      $row[] = str_replace('_', ' ', $person['province']);
      $row[] = $person['age'];
      $row[] = $person['school_level'];
      $row[] = $person['skin'];
      foreach ($person['answers'] as $answer) {
        $row[] = $answer;
      }
      $dataRows[] = $row;
    }

    array_walk_recursive($dataRows, function (&$value) {
      $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    });

    array_walk_recursive($headerRow, function (&$value) {
      $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    });

    $csv_text = $survey->title . "\n\n";
    $csv_text .= '"' . implode('";"', $headerRow) . "\"\n";

    foreach ($dataRows as $row) {
      $csv_text .= '"' . implode('";"', $row) . "\"\n";
    }

    header("Content-type: text/csv");
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=\"Survey$id-ReportByUser-" . date("Y-m-d-h-i-s") . ".csv\"");
    header("Content-Length: " . strlen($csv_text));
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Accept-Ranges: bytes");

    echo $csv_text;

    $this->view->disable();

  }

  /**
   * Download survey's results as PDF
   *
   * @author kumahacker
   */
  public function surveyReportPDFAction() {
    // getting ad's id
    $id = intval($_GET['id']);

    //get the excluded users
    $exclude = $this->excludedUsers($id);

    $survey = self::getSurvey($id);
    $title  = "{$survey->title} - " . date("d M, Y");

    $html = '<html><head><title>' . $title . '</title><style>
 				h1 {color: #5EBB47;text-decoration: underline;font-size: 24px; margin-top: none;}
 				h2{ color: #5EBB47; font-size: 16px; margin-top: none; }
 				body{font-family:Verdana;}</style><body>';
    $html .= "<br/><h1>$title</h1>";

    $questions = Connection::query("SELECT * FROM _survey_question WHERE survey = $id;");

    $i = 0;
    foreach ($questions as $question) {
      $answers = Connection::query(
        "SELECT *, (SELECT count(_survey_answer_choosen.email) 
				FROM _survey_answer_choosen 
				WHERE _survey_answer_choosen.answer = _survey_answer.id 
				AND NOT _survey_answer_choosen.email IN($exclude)) AS choosen 
				FROM _survey_answer WHERE question = {$question->id};");

      $values = [];
      foreach ($answers as $ans) {
        $values[wordwrap($ans->title, 50) . " ({$ans->choosen})"] = $ans->choosen;
      }

      $chart   = $this->getPieChart($question->title, $values, $PieChart);
      $html    .= '<table width="100%" align="center">';
      $html    .= '<tr><th align="left" colspan="2"><caption>' . $question->title . '</caption></th></tr>';
      $html    .= '<tr><td width="70%"><img src="data:image/png;base64,' . $chart . '"></td>';
      $html    .= '<td width="30%">';
      $html    .= '<table width="100%">';
      $Data    = $PieChart->pDataObject->getData();
      $Palette = $PieChart->pDataObject->getPalette();

      foreach ($Data["Series"][$Data["Abscissa"]]["Data"] as $Key => $Value) {
        $R    = $Palette[$Key]["R"];
        $G    = $Palette[$Key]["G"];
        $B    = $Palette[$Key]["B"];
        $html .= "<tr><td>";
        $html .= "<tr><td><span style=\"width:30px;height:30px;background:rgb($R,$G,$B);\">&nbsp;&nbsp;</span></td><td>$Value</td></tr>";
      }
      $html .= '</table></td></tr></table>';

      $i++;
      //if ($i % 4 == 0 && $i < $total) $html .= '<pagebreak />';
    }
    $html .= '</body></html>';

    // save the PDF and download
    $wwwroot = $this->di->get('path')['root'];

    if (!class_exists('mPDF')) {
      include_once $wwwroot . "/lib/mpdf/mpdf.php";
    }

    $mpdf = new Mpdf();
    $mpdf->WriteHTML(trim($html));
    $mpdf->Output("$title.pdf", 'D');
    $this->view->disable();
  }

  /**
   * Get image with a pie chart
   *
   * @author kumahacker
   *
   * @param string $title
   * @param array $values
   * @param object $chartObj
   *
   * @return string
   */
  private function getPieChart($title, $values, &$chartObj) {

    include_once "../lib/pChart2.1.4/class/pData.class.php";
    include_once "../lib/pChart2.1.4/class/pDraw.class.php";
    include_once "../lib/pChart2.1.4/class/pPie.class.php";
    include_once "../lib/pChart2.1.4/class/pImage.class.php";

    $MyData = new pData();
    $MyData->addPoints($values, "ScoreA");
    $MyData->setSerieDescription("ScoreA", $title);
    $MyData->addPoints(array_keys($values), "Labels");
    $MyData->setAbscissa("Labels");

    $myPicture = new pImage(500, 300, $MyData);
    $myPicture->setFontProperties([
      "FontName" => "../lib/pChart2.1.4/fonts/verdana.ttf",
      "FontSize" => 13,
      "R"        => 0,
      "G"        => 0,
      "B"        => 0,
    ]);

    $myPicture->drawText(10, 23, $title, [
      "R" => 255,
      "G" => 255,
      "B" => 255,
    ]);

    $myPicture->setShadow(TRUE, [
      "X"     => 2,
      "Y"     => 2,
      "R"     => 0,
      "G"     => 0,
      "B"     => 0,
      "Alpha" => 50,
    ]);

    $PieChart = new pPie($myPicture, $MyData);
    $PieChart->draw2DPie(250, 160, [
      "Radius"        => 120,
      "WriteValues"   => PIE_VALUE_PERCENTAGE,
      "ValuePadding"  => 10,
      "DataGapAngle"  => 0,
      "DataGapRadius" => 0,
      "Border"        => FALSE,
      "BorderR"       => 0,
      "BorderG"       => 0,
      "BorderB"       => 0,
      "ValueR"        => 0,
      "ValueG"        => 0,
      "ValueB"        => 0,
      "Shadow"        => FALSE,
    ]);

    $chartObj = $PieChart;

    ob_start();
    imagepng($myPicture->Picture);
    $img = ob_get_contents();
    ob_end_clean();

    return base64_encode($img);
  }

  /**
   * Audience report
   */
  public function audienceAction() {
    $id                 = intval($_GET['id']);
    $survey             = self::getSurvey($id);
    $participants_table = uniqid('_survey_participants_');
    $total_answer       = Connection::query("SELECT COUNT(*) AS total FROM _survey_answer_choosen WHERE survey =  $id;")[0]->total * 1;

    Connection::query("CREATE TABLE $participants_table (email varchar(255), total bigint(11));");
    Connection::query("INSERT INTO $participants_table SELECT email, COUNT(email) AS total FROM `_survey_answer_choosen` WHERE survey = $id GROUP BY email HAVING total = {$total_answer};");

    $results = function ($fields, $participants_table) {
      if (!is_array($fields)) {
        $fields = [$fields => 'true'];
      }

      $sql = [];
      foreach ($fields as $field => $where) {
        $field_parts = explode(' ', trim($field));
        $sql[] =
          "SELECT $field, COUNT(id) AS total
          FROM ( 
            SELECT *, TIMESTAMPDIFF(YEAR, date_of_birth, NOW()) as age 
            FROM person
            WHERE updated_by_user = 1 
              AND active = 1
              AND email IN (SELECT email FROM $participants_table)
            ) subq 
          WHERE $where
          GROUP BY " . array_pop($field_parts);
      }

      return Connection::query(implode(" UNION ", $sql));
    };

    $this->view->setVars([
      'title'                          => "Audience for survey: <b>{$survey->title}</b>",
      'total_answer'                   => $total_answer,
      'totals_by_gender'               => $results('gender', $participants_table),
      'totals_by_province'             => $results('province', $participants_table),
      'totals_by_highest_school_level' => $results('highest_school_level', $participants_table),
      'totals_by_skin'                 => $results('skin', $participants_table),
      'totals_by_age'                  => $results([
        "'Menos de 17' AS age" => " age < 17 ",
        "'17-21' AS age"       => " age BETWEEN 17 AND 21 ",
        "'22-35' AS age"       => " age BETWEEN 22 AND 35 ",
        "'36-55' AS age"       => " age BETWEEN 36 AND 55 ",
        "'Mas de 55' AS age"   => " age > 55 ",
      ], $participants_table),
    ]);

    Connection::query("DROP TABLE $participants_table;");
  }
}
