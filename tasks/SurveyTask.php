<?php

/**
 * Survey Reminder Task
 *
 * @author kuma
 */
class SurveyTask extends \Phalcon\Cli\Task
{
	public function mainAction()
	{
		// get people with unfinished surveys
		$connection = new Connection();
		$unfinishedSurveys = $connection->query("
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
			AND A.days_since >= 7
			AND B.active = 1
			AND DATEDIFF(B.deadline, B.date_created) > 0");

		// send notifications to users
		foreach ($unfinishedSurveys as $us) {
			// @TODO
		}
	}
}
