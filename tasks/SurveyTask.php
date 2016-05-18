<?php

/**
 * Survey Reminder Task
 * 
 * @author kuma
 */
class SurveyTask extends \Phalcon\Cli\Task
{
	public function mainAction ()
	{
		// inicialize supporting classes
		$timeStart = time();
		$connection = new Connection();
		$email = new Email();
		$service = new Service();
		$service->showAds = true;
		$render = new Render();
		$response = new Response();
		$wwwroot = $this->di->get('path')['root'];
		$log = "";

		// get people who did not finish a survey for the last 3 days
		$surveys = $connection->deepQuery("
			SELECT A.*, B.title, B.deadline, B.value FROM 
			(
				SELECT email, survey,  
				DATEDIFF(CURRENT_DATE, MAX(date_choosen)) as days_since,
				(
					SELECT COUNT(*) 
					FROM _survey_question 
					WHERE _survey_question.survey = _survey_answer_choosen.survey
				) as total, 
				COUNT(question) as choosen from _survey_answer_choosen GROUP BY email, survey
			) A
			JOIN _survey B
			ON A.survey = B.id
			WHERE A.total > A.choosen 
			AND A.days_since >= 7
			AND B.active = 1
			AND DATEDIFF(B.deadline, B.date_created) > 0
			AND A.email NOT IN (SELECT DISTINCT email FROM remarketing WHERE type='SURVEY')");

		// send emails to users
		$log .= "\nSURVEY REMARKETING (".count($surveys).")\n";
		foreach ($surveys as $survey)
		{
			$content = array(
				"survey" => $survey->survey,
				"days" => $survey->days_since,
				"missing" => $survey->total - $survey->choosen,
				"title" => $survey->title,
				"deadline" => $survey->deadline,
				"value" => $survey->value
			);

			// create html response
			$response->setResponseSubject("No queremos que pierda \${$survey->value}");
			$response->createFromTemplate('surveyReminder.tpl', $content);
			$response->internal = true;

			// send email to the person
			$html = $render->renderHTML($service, $response);
			$email->sendEmail($survey->email, $response->subject, $html);

			// add entry to remarketing
			$connection->deepQuery("INSERT INTO remarketing(email, type) VALUES ('{$survey->email}', 'SURVEY');");

			// display notifications
			$log .= "\t{$survey->email} | surveyID: {$survey->survey} \n";
		}

		// get final delay
		$timeEnd = time();
		$timeDiff = $timeEnd - $timeStart;

		// printing log
		$log .= "EXECUTION TIME: $timeDiff seconds\n\n";
		echo $log;

		// saving the log
		$logger = new \Phalcon\Logger\Adapter\File("$wwwroot/logs/surveyreminder.log");
		$logger->log($log);
		$logger->close();

		// save the status in the database
		$connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='survey'");
	}
}