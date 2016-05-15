<?php

/**
 * Survey Reminder Task
 * 
 * @author kuma
 *
 */
class SurveyRiminderTask extends \Phalcon\Cli\Task
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
        $utils = new Utils();
        $wwwroot = $this->di->get('path')['root'];
        $log = "";
        
        // get surveys
        $surveys = $connection->deepQuery("SELECT *, DATEDIFF(deadline, CURRENT_DATE) as deaddays 
                FROM _survey WHERE active = 1 AND DATEDIFF(deadline, CURRENT_DATE) < 3 ;");
        
        $who = array();
        
        // searching for users
        foreach ($surveys as $survey) {
            
            $r = $connection->deepQuery(
                    "SELECT * FROM (SELECT email, survey, (SELECT count(*) 
    		         FROM _survey_question 
    		         WHERE _survey_question.survey = _survey_answer_choosen.survey) as total, 
    		         count(question)as choosen from _survey_answer_choosen GROUP BY email, survey) subq
    		         WHERE subq.total>subq.choosen AND subq.survey = {$survey->id};");
            
            if ($r !== false) {
                foreach ($r as $item) {
                    if (! isset($who[$item->email])) $who[$item->email] = array();
                    $who[$item->email][$item->survey] = $survey;
                }
            }
        }
        
        // send emails to users
        foreach ($who as $useremail => $surveys){
            // create html response
            $response->createFromTemplate('surveyReminder.tpl', array(
                    'surveys' => $surveys
            ));
            
            $response->internal = true;
            $html = $render->renderHTML($service, $response);
            
            // send email to the $person->email
            $email->sendEmail($useremail, "Encuestas que te faltan por terminar", $html);
            
            // move remarketing to the next state and add +1 credits
            $connection->deepQuery("INSERT INTO remarketing(email, type) VALUES ('{$person->email}', 'SURVEYREMINDER');");

            // display notifications
			$log .= "\t{$useremail}\n";
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
        $connection->deepQuery("UPDATE task_status SET executed=CURRENT_TIMESTAMP, delay='$timeDiff' WHERE task='surveyreminder'");
        
    }
}