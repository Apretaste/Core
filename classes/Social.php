<?php

// group of functions for social purposes

class Social
{
	private $countries = array();

	/**
	 * Load resources needed for the class to work
	 *
	 * @author salvipascual
     * @author kuma
	 */
	public function __construct()
	{
		// load the list of countries
		$connection = new Connection();
		$countries = $connection->deepQuery("SELECT * FROM countries;");
		foreach ($countries as $c)
		    if (isset($c->code) && isset($c->es) && isset($c->en))
		        $this->countries[$c->code] = ["es" => $c->es, "en" => $c->en];
	}

	/**
	 * Return description of profile as a paragraph, used for social services
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @param String $lang
	 * @return String
	 */
	 public function profileToText($profile, $lang="es")
	 {
		switch ($lang)
		{
			case 'es': return $this->profileToTextSpanish($profile);
			case 'en': return $this->profileToTextEnglish($profile);
			default: return $this->profileToTextSpanish($profile);
		}
	 }

	/**
	 * Return description of profile as a paragraph, in Spanish
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @return String
	 */
	private function profileToTextSpanish($profile)
	{
		// get the age
		$age = empty($profile->date_of_birth) ? "" : date_diff(date_create($profile->date_of_birth), date_create('today'))->y;

		// get the gender
		$gender = "";
		if ($profile->gender == "M") $gender = "hombre";
		if ($profile->gender == "F") $gender = "mujer";

		// get the final vowel based on the gender
		$genderFinalVowel = "o";
		if ($profile->gender == "F") $genderFinalVowel = "a";

		// get the eye color
		$eyes = "";
		if ($profile->eyes == "NEGRO") $eyes = "negros";
		if ($profile->eyes == "CARMELITA") $eyes = "carmelita";
		if ($profile->eyes == "AZUL") $eyes = "azules";
		if ($profile->eyes == "VERDE") $eyes = "verdes";
		if ($profile->eyes == "AVELLANA") $eyes = "avellana";

		// get the eye tone
		$eyesTone = "";
		if ($profile->eyes == "NEGRO" || $profile->eyes == "CARMELITA" || $profile->eyes == "AVELLANA") $eyesTone = "oscuros";
		if ($profile->eyes == "AZUL" || $profile->eyes == "VERDE") $eyesTone = "claros";

		// get the skin color
		$skin = "";
		if ($profile->skin == "NEGRO") $skin = "negr$genderFinalVowel";
		if ($profile->skin == "BLANCO") $skin = "blanc$genderFinalVowel";
		if ($profile->skin == "MESTIZO") $skin = "mestiz$genderFinalVowel";

		// get the type of body
		$bodyType = "";
		if ($profile->body_type == "DELGADO") $bodyType = "soy flac$genderFinalVowel";
		if ($profile->body_type == "MEDIO") $bodyType = "no soy ni flac$genderFinalVowel ni grues$genderFinalVowel";
		if ($profile->body_type == "EXTRA") $bodyType = "tengo unas libritas de m&aacute;s";
		if ($profile->body_type == "ATLETICO") $bodyType = "tengo un cuerpazo atl&eacute;tico";

		// get the hair color
		$hair = "";
		if ($profile->hair == "TRIGUENO") $hair = "trigue&ntilde;o";
		if ($profile->hair == "CASTANO") $hair = "casta&ntilde;o";
		if ($profile->hair == "RUBIO") $hair = "rubio";
		if ($profile->hair == "NEGRO") $hair = "negro";
		if ($profile->hair == "ROJO") $hair = "rojizo";
		if ($profile->hair == "BLANCO") $hair = "canoso";

		// get the place where the person live
		$province = false;
		if ($profile->province == "PINAR_DEL_RIO") $province = "Pinar del R&iacute;o";
		if ($profile->province == "LA_HABANA") $province = "La Habana";
		if ($profile->province == "ARTEMISA") $province = "Artemisa";
		if ($profile->province == "MAYABEQUE") $province = "Mayabeque";
		if ($profile->province == "MATANZAS") $province = "Matanzas";
		if ($profile->province == "VILLA_CLARA") $province = "Villa Clara";
		if ($profile->province == "CIENFUEGOS") $province = "Cienfuegos";
		if ($profile->province == "SANCTI_SPIRITUS") $province = "Sancti Sp&iacute;ritus";
		if ($profile->province == "CIEGO_DE_AVILA") $province = "Ciego de &Aacute;vila";
		if ($profile->province == "CAMAGUEY") $province = "Camaguey";
		if ($profile->province == "LAS_TUNAS") $province = "Las Tunas";
		if ($profile->province == "HOLGUIN") $province = "Holgu&iacute;n";
		if ($profile->province == "GRANMA") $province = "Granma";
		if ($profile->province == "SANTIAGO_DE_CUBA") $province = "Santiago de Cuba";
		if ($profile->province == "GUANTANAMO") $province = "Guant&aacute;namo";
		if ($profile->province == "ISLA_DE_LA_JUVENTUD") $province = "Isla de la Juventud";

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : (isset($this->countries[$countryCode]) ? $this->countries[$countryCode]['es'] : false);
		$usstate = isset($profile->usstate) ? (empty(trim($profile->usstate)) ? false : $profile->usstate) : false;
		$city = empty(trim($profile->city)) ? false : $profile->city;

		// get the location
		$location = "Vivo en ";
		if ($city) $location .= "la ciudad de $city, ";
		if ($countryCode == "US" && $usstate) $location .= strtoupper($usstate) . ", ";
		if ($countryCode == "CU" && $province) $location .= "provincia $province, ";
		if ($country) $location .= $country;
		if($location == "Vivo en ") $location = "Aunque prefiero no decir donde vivo, ";

		// get highest educational level
		$education = "";
		if ($profile->highest_school_level == "PRIMARIO") $education = "tengo sexto grado";
		if ($profile->highest_school_level == "SECUNDARIO") $education = "soy graduad$genderFinalVowel de la secundaria";
		if ($profile->highest_school_level == "TECNICO") $education = "soy t&acute;cnico medio";
		if ($profile->highest_school_level == "UNIVERSITARIO") $education = "soy universitari$genderFinalVowel";
		if ($profile->highest_school_level == "POSTGRADUADO") $education = "tengo estudios de postgrado";
		if ($profile->highest_school_level == "DOCTORADO") $education = "tengo un doctorado";

		// get marital status
		$maritalStatus = "";
		if ($profile->marital_status == "SOLTERO") $maritalStatus = "estoy solter$genderFinalVowel";
		if ($profile->marital_status == "SALIENDO") $maritalStatus = "estoy saliendo con alguien";
		if ($profile->marital_status == "COMPROMETIDO") $maritalStatus = "estoy comprometid$genderFinalVowel";
		if ($profile->marital_status == "CASADO") $maritalStatus = "soy casad$genderFinalVowel";

		// get occupation
		$occupation = (empty($profile->occupation) || strlen($profile->occupation) < 5) ? false : strtolower($profile->occupation);
		if(stripos($occupation, "studiant") !== false) $occupation = "";

		// get religion
		$religions = array(
			'ATEISMO' => "soy ate$genderFinalVowel",
			'SECULARISMO' => 'no tengo creencia religiosa',
			'AGNOSTICISMO' => "soy agn&oacute;stic$genderFinalVowel",
			'ISLAM' => 'soy musulm&aacute;n',
			'JUDAISTA' => "soy jud&iacute;o$genderFinalVowel",
			'ABAKUA' => 'soy abaku&aacute;',
			'SANTERO' => "soy santer$genderFinalVowel",
			'YORUBA' => 'profeso la religi&oacute;n yoruba',
			'BUDISMO' => 'soy budista',
			'CATOLICISMO' => "soy cat&oacute;lic$genderFinalVowel",
			'OTRA' => '',
			'CRISTIANISMO' => "soy cristian$genderFinalVowel"
		);
		$religion = empty($profile->religion) ? "" : $religions[$profile->religion];

		// create the message
		$message = "Hola";
		if ( ! empty(trim($profile->first_name))) $message .= ", mi nombre es " . ucfirst(strtolower(trim($profile->first_name)));
		if ( ! empty($age)) $message .= ", tengo $age a&ntilde;os";
		if ( ! empty($gender)) $message .= ", soy $gender";
		if ( ! empty($religion)) $message .= ", soy $religion";
		if ( ! empty($skin)) $message .= ", soy $skin";
		if ( ! empty($eyes)) $message .= ", de ojos $eyesTone (color $eyes)";
		if ( ! empty($hair)) $message .= ", soy de pelo $hair ";
		if ( ! empty($bodyType)) $message .= " y $bodyType";
		$message .= ". $location";
		if ( ! empty($education)) $message .= ", $education";
		if ( ! empty($occupation)) $message .= ", trabajo como $occupation";
		if ( ! empty($maritalStatus)) $message .= " y $maritalStatus";
		$message .= ".";

		// remove double spaces
		$message = str_replace(', ,', ',', $message);
		$message = preg_replace('/([\s])\1+/', ' ', $message);
		return ucfirst($message);
	}

	/**
	 * Return description of profile as a paragraph, in English
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @return String
	 */
	private function profileToTextEnglish($profile)
	{
		// get the age
		$age = empty($profile->date_of_birth) ? "" : date_diff(date_create($profile->date_of_birth), date_create('today'))->y;

		// get the gender
		$gender = "";
		if ($profile->gender == "M") $gender = "male";
		if ($profile->gender == "F") $gender = "female";

		// get the eye color
		$eyes = "";
		if ($profile->eyes == "NEGRO") $eyes = "black";
		if ($profile->eyes == "CARMELITA") $eyes = "brown";
		if ($profile->eyes == "AZUL") $eyes = "blue";
		if ($profile->eyes == "VERDE") $eyes = "green";
		if ($profile->eyes == "AVELLANA") $eyes = "hazelnut";

		// get the eye tone
		$eyesTone = "";
		if ($profile->eyes == "NEGRO" || $profile->eyes == "CARMELITA" || $profile->eyes == "AVELLANA") $eyesTone = "dark";
		if ($profile->eyes == "AZUL" || $profile->eyes == "VERDE") $eyesTone = "light";

		// get the skin color
		$skin = "";
		if ($profile->skin == "NEGRO") $skin = "black";
		if ($profile->skin == "BLANCO") $skin = "white";
		if ($profile->skin == "MESTIZO") $skin = "mestizo";

		// get the type of body
		$bodyType = "";
		if ($profile->body_type == "DELGADO") $bodyType = "I am skinny";
		if ($profile->body_type == "MEDIO") $bodyType = "I am neither skinny nor fat";
		if ($profile->body_type == "EXTRA") $bodyType = "I have few extra pounds";
		if ($profile->body_type == "ATLETICO") $bodyType = "my body is athletic";

		// get the hair color
		$hair = "";
		if ($profile->hair == "TRIGUENO") $hair = "brown";
		if ($profile->hair == "CASTANO") $hair = "chestnut";
		if ($profile->hair == "RUBIO") $hair = "blonde";
		if ($profile->hair == "ROJO") $hair = "red";
		if ($profile->hair == "NEGRO") $hair = "black";
		if ($profile->hair == "BLANCO") $hair = "white";

		// get the place where the person live
		$province = false;
		if ($profile->province == "PINAR_DEL_RIO") $province = "Pinar del R&iacute;o";
		if ($profile->province == "LA_HABANA") $province = "La Habana";
		if ($profile->province == "ARTEMISA") $province = "Artemisa";
		if ($profile->province == "MAYABEQUE") $province = "Mayabeque";
		if ($profile->province == "MATANZAS") $province = "Matanzas";
		if ($profile->province == "VILLA_CLARA") $province = "Villa Clara";
		if ($profile->province == "CIENFUEGOS") $province = "Cienfuegos";
		if ($profile->province == "SANCTI_SPIRITUS") $province = "Sancti Sp&iacute;ritus";
		if ($profile->province == "CIEGO_DE_AVILA") $province = "Ciego de &Aacute;vila";
		if ($profile->province == "CAMAGUEY") $province = "Camaguey";
		if ($profile->province == "LAS_TUNAS") $province = "Las Tunas";
		if ($profile->province == "HOLGUIN") $province = "Holgu&iacute;n";
		if ($profile->province == "GRANMA") $province = "Granma";
		if ($profile->province == "SANTIAGO_DE_CUBA") $province = "Santiago de Cuba";
		if ($profile->province == "GUANTANAMO") $province = "Guant&aacute;namo";
		if ($profile->province == "ISLA_DE_LA_JUVENTUD") $province = "Isla de la Juventud";

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : $this->countries[$countryCode]['en'];
		$usstate = empty(trim($profile->usstate)) ? false : $profile->usstate;
		$city = empty(trim($profile->city)) ? false : $profile->city;

		// get the location
		$location = "I live in ";
		if ($city) $location .= "$city city, ";
		if ($countryCode == "US" && $usstate) $location .= strtoupper($usstate) . ", ";
		if ($countryCode == "CU" && $province) $location .= "province $province, ";
		if ($country) $location .= $country;
		if($location == "I live in ") $location = "Although I prefer not to say where I live, ";

		// get highest educational level
		$education = "";
		if ($profile->highest_school_level == "PRIMARIO") $education = "leave school at elementary";
		if ($profile->highest_school_level == "SECUNDARIO") $education = "I have a high school degree";
		if ($profile->highest_school_level == "TECNICO") $education = "I have a associate degree";
		if ($profile->highest_school_level == "UNIVERSITARIO") $education = "I am a college graduate";
		if ($profile->highest_school_level == "POSTGRADUADO") $education = "I got a Masters degree";
		if ($profile->highest_school_level == "DOCTORADO") $education = "I have a PHD";

		// get marital status
		$maritalStatus = "";
		if ($profile->marital_status == "SOLTERO") $maritalStatus = "I am single";
		if ($profile->marital_status == "SALIENDO") $maritalStatus = "I am dating someone";
		if ($profile->marital_status == "COMPROMETIDO") $maritalStatus = "I am engaged";
		if ($profile->marital_status == "CASADO") $maritalStatus = "I am married";

		// get occupation
		$occupation = (empty($profile->occupation) || strlen($profile->occupation) < 5) ? false : strtolower($profile->occupation);
		if(stripos($occupation, "studiant") !== false) $occupation = "";

		// get religion
		$religions = array(
			'ATEISMO' => "I am an atheist",
			'SECULARISMO' => "I do not have any religious belief",
			'AGNOSTICISMO' => "I am agnostic",
			'ISLAM' => 'I am a Muslim',
			'JUDAISTA' => "I am Jewish",
			'ABAKUA' => 'I am an abaku&aacute;',
			'SANTERO' => "I practice Santer&iacute;a",
			'YORUBA' => 'I like Yoruba',
			'BUDISMO' => 'I am Buddhist',
			'CATOLICISMO' => "I am Catholic",
			'CRISTIANISMO' => "I am Christian",
			'OTRA' => ''
		);
		$religion = empty($profile->religion) ? "" : $religions[$profile->religion];

		// create the message
		$message = "Hi";
		if ( ! empty(trim($profile->first_name))) $message .= ", my name is " . ucfirst(strtolower(trim($profile->first_name)));
		if ( ! empty($age)) $message .= ", I am $age years old";
		if ( ! empty($gender)) $message .= ", I am $gender";
		if ( ! empty($religion)) $message .= ", $religion";
		if ( ! empty($skin)) $message .= ", I am $skin";
		if ( ! empty($eyes)) $message .= ", my eyes are $eyesTone $eyes";
		if ( ! empty($hair)) $message .= ", my hair is $hair ";
		if ( ! empty($bodyType)) $message .= " and $bodyType";
		$message .= ". $location";
		if ( ! empty($education)) $message .= ", $education";
		if ( ! empty($occupation)) $message .= ", I work as $occupation";
		if ( ! empty($maritalStatus)) $message .= " and $maritalStatus";
		$message .= ".";

		// remove double spaces
		$message = str_replace(', ,', ',', $message);
		$message = preg_replace('/([\s])\1+/', ' ', $message);
		return ucfirst($message);
	}

	/**
	 * Get the completion percentage of a profile
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @return Number, percentage of completion
	 * */
	public function getProfileCompletion($profile)
	{
		// get an array of the valid profile fields
		$keys = array("first_name","last_name","date_of_birth","gender","eyes","skin","body_type","hair","city","highest_school_level","occupation","marital_status","interests","picture","sexual_orientation","religion","country");

		// count how much filled is the profile
		$counter = 0;
		if( ! empty($profile->usstate) ||  ! empty($profile->province)) $counter++;
		foreach ($keys as $key)
		{
			if( ! empty($profile->$key)) $counter++;
		}

		// calculate and return the completion percentage
		$total = count($keys) + 1;
		return ($counter  * 100) / $total;
	}

	/**
	 * Prepare a user profile to be displayed properly
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @return Number, percentage of completion
	 * */
	public function prepareUserProfile($profile)
	{
		// get the person's age
		$profile->age = empty($profile->date_of_birth) ? "" : date_diff(date_create($profile->date_of_birth), date_create('today'))->y;

		// try to guest the location based on the domain
		$inCuba = strrpos($profile->email, ".cu") == strlen($profile->email)-strlen(".cu");
		if(empty($profile->country) && $inCuba) $profile->country = "CU";

		// get the most accurate location as possible
		$location = $profile->country;
		if($profile->city) $location = $profile->city;
		if(isset($profile->usstate)) $location = $profile->usstate;
		if($profile->province) $location = $profile->province;
		$location = str_replace("_", " ", $location);
		$profile->location = ucwords(strtolower($location));

		// get the person's full name
		$fullName = "{$profile->first_name} {$profile->middle_name} {$profile->last_name} {$profile->mother_name}";
		$profile->full_name = trim(preg_replace("/\s+/", " ", $fullName));

		// get the image of the person
		if($profile->picture)
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];
			$wwwhttp = $di->get('path')['http'];
			$profile->picture_internal = "$wwwroot/public/profile/{$profile->picture}.jpg";
			$profile->picture_public = "$wwwhttp/profile/{$profile->picture}.jpg";
			$profile->picture = true;
		}

		// get the interests as an array
		$profile->interests = preg_split('@,@', $profile->interests, NULL, PREG_SPLIT_NO_EMPTY);

		// remove whitespaces at the begining and ending of string fields
		foreach ($profile as $key=>$value) if( ! is_array($value)) $profile->$key = trim($value);

		// get the completion percentage of your profile
		$profile->completion = $this->getProfileCompletion($profile);

		// get the about me section
		if (empty($profile->about_me)) $profile->about_me = $this->profileToText($profile);

		// remove dangerous attributes from the response
		unset($profile->pin,$profile->insertion_date,$profile->last_access,$profile->active,$profile->last_update_date,$profile->updated_by_user,$profile->cupido,$profile->source,$profile->blocked);

		return $profile;
	}
}
