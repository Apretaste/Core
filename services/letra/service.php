<?php

class Letra extends Service
{
	/**
	 * Function excecuted once the service Letra is called
	 * 
	 * @param Request
	 * */
	public function main($request){
		// search for the lyric
		$letra = $this->searchForLyricOnTheWeb($request->argument);

		// get similar lyrics
		$otrasLetras = $this->searchForSimilarSongs($request->argument);

		// saving into the private database of the app
		$this->database->query("INSERT INTO letras VALUES ('{$request->argument}', '{$request->requestor->email}', 0)");

		// check number of times viewed from the private database
		$views = $this->database->query("SELECT titulo FROM letras WHERE titulo = '{$request->argument}'");
		$numero_letras = count($views);

		// create a json object to send to the template
		$json = '
			{
				"nombre_cancion": "'.$request->argument.'",
				"letra_html": "'.$letra.'",
				"veces_buscada": "'.$numero_letras.'",
				"letra_1_caption": "'.$otrasLetras[0].'",
				"letra_1_link": "'.$this->utils->getLinkToService("letra", "", $otrasLetras[0]).'",
				"letra_2_caption": "'.$otrasLetras[1].'",
				"letra_2_link": "'.$this->utils->getLinkToService("letra", "", $otrasLetras[1]).'",
				"letra_3_caption": "'.$otrasLetras[2].'",
				"letra_3_link": "'.$this->utils->getLinkToService("letra", "", $otrasLetras[2]).'"
			}
		';

		// configure the response object to use a user defined template
		// display error if the JSON is invalid (http://www.json.com/)
		$this->response->createUserDefinedResponse("basic.tpl", $json);
	}

	/**
	 * Search for a lyric on the Internet and return the HTML
	 * 
	 * @author salvipascual
	 * @param String, name of the lyric 
	 * @return String HTML
	 * */
	private function searchForLyricOnTheWeb($lyricName){
		return "Go!<br /><br />Stapled shut, inside an outside world and I'm<br />Sealed in tight, bizarre but right at home<br />Claustrophobic, closing in and I'm<br />Catastrophic, not again<br />I'm smeared across the page, and doused in gasoline<br />I wear you like a stain, yet I'm the one who's obscene<br />Catch me up on all your sordid little insurrections,<br />I've got no time to lose, and I'm just caught up in all the cattle<br /><br />Fray the strings<br />Throw the shapes<br />Hold your breath<br />Listen!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />I'm ripped across the ditch, and settled in the dirt and I'm<br />I wear you like a stitch, yet I'm the one who's hurt<br />Pay attention to your twisted little indiscretions<br />I've got no right to win, I'm just caught up in all the battles<br /><br />Locked in clutch<br />Pushed in place<br />Hold your breath<br />Listen!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />My end<br />It justifies my means<br />All I ever do is delay<br />My every attempt to evade<br />The end of the road and my end<br />It justifies my means<br />All I ever do is delay<br />My every attempt to evade<br />THE END OF THE ROAD!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />I am a world before I am a man<br />I was a creature before I could stand<br />I will remember before I forget<br />BEFORE I FORGET THAT!<br /><br />Yeah, yeah, yeah, yeah<br />Yeah, yeah, yeah, OH!";
	}

	/**
	 * Get songs similar to the one displayed 
	 * 
	 * @author salvipascual
	 * @param String, name of the lyric
	 * @return Array 
	 * */
	private function searchForSimilarSongs($lyricName){
		return Array("pandemonium", "sweet dreams", "wait and bleed");
	}
}
