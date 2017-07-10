<?php

// include the Twitter library
use Abraham\TwitterOAuth\TwitterOAuth;

class PizarraTask extends \Phalcon\Cli\Task
{
	private $KEY = "nXbz7LXFcKSemSb9v2pUh5XWV";
	private $KEY_SECRET = "kjSF6NOppBgR3UsP4u9KjwavrLUFGOcWEeFKmcWCZQyLLpOWCm";
	private $TOKEN = "4247250736-LgRlKf0MgOLQZY6VnaZTJUKTuDU7q0GefcEPYyB";
	private $TOKEN_SECRET = "WXpiTky2v9RVlnJnrwSYlX2BOmJqv8W3Sfb1Ve61RrWa3";

	/**
	 * sources to pull from twitter
	 */
	private $sources = array(
		"salvipascual+chistoso@gmail.com" => "@EresChiste",
		"salvipascual+etecsa@gmail.com" => "@ETECSA_Cuba",
		"salvipascual+cafefuerte@gmail.com" => "@CafeFuertecom",
		"salvipascual+cubadebate@gmail.com" => "@cubadebate",
		"salvipascual+frases@gmail.com" => "@CitasCelebrs",
		"salvipascual+filosofia@gmail.com" => "@ifilosofia"
	);

	/**
	 * Get the content from outside sources and post it in Pizarra
	 *
	 * @author salvipascual
	 */
	public function mainAction()
	{
		$utils = new Utils();
		$connection = new Connection();

		// create a twitter handler
		$twitter = new TwitterOAuth($this->KEY, $this->KEY_SECRET, $this->TOKEN, $this->TOKEN_SECRET);

		// loop all sources and get their content
		foreach ($this->sources as $email => $query)
		{
			// get the list of tweets from the user feed
			$tweets = $twitter->get("statuses/user_timeline", array("screen_name"=>$query, "count"=>50));

			// pick the newest, unpicked tweet form the list
			foreach ($tweets as $tweet)
			{
				// do not post replies or retweets
				$note = $tweet->text;
				if($this->startsWith($note, "@") || $this->startsWith($note, "RT")) continue;

				// trim, escape and format text
				$note = str_replace("\n", " ", $note);
				$note = str_replace("'", "", $note);
				$note = str_replace("@", "", $note);
				$note = str_replace("“", '"', $note);
				$note = str_replace("”", '"', $note);
				$note = $utils->removeTildes($note); // removes Spanish tildes
				$note = preg_replace('/[^A-Za-z0-9\- \/\.:_]/', '', $note); // removes special chars.
				$note = preg_replace('/([\s])\1+/', ' ', $note);
				$note = substr($note, 0, 140);

				// check if that nota already exist
				$notescount = $connection->query("SELECT COUNT(id) as total FROM _pizarra_notes WHERE `text`='$note'");
				if($notescount[0]->total > 0) continue;

				// save note into the database
				$connection->query("INSERT INTO _pizarra_notes (email,`text`,auto) VALUES ('$email', '$note',1)");
				break;
			}
		}

		// save the status in the database
		$connection->query("UPDATE task_status SET executed=CURRENT_TIMESTAMP WHERE task='pizarra'");
	}

	/**
	 * Check if a String starts with a character
	 */
	function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}
