<?php

use Phalcon\Mvc\Controller;
  
class TestController extends Controller
{
	public function indexAction()
	{
		$email = "me@gmail.com";
		$connection = new Connection();
   			// check if the person was invited to use Apretaste
			$sql = "SELECT * FROM invitations WHERE email_invited = '$email' AND used='0'";
			$invitations = $connection->deepQuery($sql);

			if(count($invitations)>0) {
				// create tickets for all the invitors. When a person 
				// is invited by more than one person, they all get tickets
				$sql = "START TRANSACTION;";
				foreach ($invitations as $invite) {
					$sql .= "INSERT INTO ticket (email, paid) VALUES ('{$invite->email_inviter}', 0);";
					$sql .= "UPDATE invitations SET used='1' WHERE invitation_id = '{$invite->invitation_id}';";
				}
				$sql .= "COMMIT;";
				$connection->deepQuery($sql);
			}
	}
}
