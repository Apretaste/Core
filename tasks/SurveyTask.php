<?php

/**
 * Survey Reminder Task
 * @author kuma
 */
class SurveyTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		$timeStart = time();

		// get people with unfinished surveys
		$unfinishedSurveys = Connection::query("
			SELECT A.*, B.title, B.deadline, B.value FROM
			(
				SELECT email, survey,
				DATEDIFF(CURRENT_DATE, MAX(date_choosen)) as days_since,
				(
					SELECT COUNT(id)
					FROM _survey_question
					WHERE _survey_question.survey = _survey_answer_choosen.survey
				) as total,
				COUNT(question) as choosen from _survey_answer_choosen GROUP BY email, survey
			) A
			JOIN _survey B
			ON A.survey = B.id
			WHERE A.total > A.choosen
			AND A.days_since >= 3
			AND B.active = 1
			AND B.deadline > NOw()");

		// send notifications to users
		foreach ($unfinishedSurveys as $us) {
			$text = "Has dejado una encuesta sin terminar, tu opinión es importante. Completala y gana §{$us->value}";
			$link = "ENCUESTA {$us->survey}";
			Utils::addNotification($us->email, "Encuesta", $text, $link);
		}

		// save the status in the database
		$timeDiff = time() - $timeStart;
		Connection::query("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='survey'");
	}
}
