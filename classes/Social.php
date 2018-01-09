<?php

// group of functions for social purposes

class Social
{
	private $countries = array();

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
		if ($profile->body_type == "EXTRA") $bodyType = "tengo unas libritas de mas";
		if ($profile->body_type == "ATLETICO") $bodyType = "tengo un cuerpazo atletico";

		// get the hair color
		$hair = "";
		if ($profile->hair == "TRIGUENO") $hair = "triguenno";
		if ($profile->hair == "CASTANO") $hair = "castanno";
		if ($profile->hair == "RUBIO") $hair = "rubio";
		if ($profile->hair == "NEGRO") $hair = "negro";
		if ($profile->hair == "ROJO") $hair = "rojizo";
		if ($profile->hair == "BLANCO") $hair = "canoso";

		// get the place where the person lives
		$province = ($profile->province) ? $this->getProvinceNameFromCode($profile->province) : false;

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : $this->getCountryNameFromCode($countryCode);
		$usstate = empty(trim($profile->usstate)) ? false : $this->getStateNameFromCode($profile->usstate);
		$city = empty(trim($profile->city)) ? false : $profile->city;

		// get the location
		if ($city && $country) $location = "Vivo en $country, en la ciudad de $city";
		elseif ($countryCode=="US" && $usstate) $location = "Vivo en $usstate, $country";
		elseif ($countryCode == "CU" && $province) $location = "Vivo en la provincia de $province";
		elseif ($country) $location = "Vivo en $country";
		else $location = "Aunque prefiero no decir donde vivo";

		// get highest educational level
		$education = "";
		if ($profile->highest_school_level == "PRIMARIO") $education = "tengo sexto grado";
		if ($profile->highest_school_level == "SECUNDARIO") $education = "soy graduad$genderFinalVowel de la secundaria";
		if ($profile->highest_school_level == "TECNICO") $education = "soy tecnico medio";
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
			'AGNOSTICISMO' => "soy agnostic$genderFinalVowel",
			'ISLAM' => 'soy musulman',
			'JUDAISTA' => "soy judio$genderFinalVowel",
			'ABAKUA' => 'soy abakua',
			'SANTERO' => "soy santer$genderFinalVowel",
			'YORUBA' => 'profeso la religion yoruba',
			'BUDISMO' => 'soy budista',
			'CATOLICISMO' => "soy catolic$genderFinalVowel",
			'OTRA' => '',
			'CRISTIANISMO' => "soy cristian$genderFinalVowel"
		);
		$religion = empty($profile->religion) ? "" : $religions[$profile->religion];

		// create the message
		$message = "Hola";
		if ( ! empty(trim($profile->first_name))) $message .= ", mi nombre es " . ucfirst(strtolower(trim($profile->first_name)));
		if ( ! empty($age)) $message .= ", tengo $age annos";
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

		// convert text to UTF-8
		$message = utf8_encode($message);

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

		// get the place where the person lives
		$province = ($profile->province) ? $this->getProvinceNameFromCode($profile->province) : false;

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : $this->getCountryNameFromCode($countryCode, 'en');
		$usstate = empty(trim($profile->usstate)) ? false : $this->getStateNameFromCode($profile->usstate);
		$city = empty(trim($profile->city)) ? false : $profile->city;

		// get the location
		if ($city && $country) $location = "I live in $country, in $city city";
		elseif ($countryCode=="US" && $usstate) $location = "My home is $usstate, $country";
		elseif ($countryCode == "CU" && $province) $location = "I live in the province of $province, in $country";
		elseif ($country) $location = "My country is $country";
		else $location = "Although I prefer not to say where I live";

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
			'ABAKUA' => 'I am an abakua',
			'SANTERO' => "I practice Santeria",
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

		// convert text to UTF-8
		$message = utf8_encode($message);

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
	 * @param String $lang, language to return the profile text
	 * @return Number, percentage of completion
	 * */
	public function prepareUserProfile($profile, $lang=false)
	{
		// ensure only use known languages and Spanish is default
		if( ! $lang && $profile->lang) $lang = $profile->lang;
		if( ! in_array($lang, ["en","es"])) $lang = "es";

		// get the person's age
		$profile->age = empty($profile->date_of_birth) ? "" : date_diff(date_create($profile->date_of_birth), date_create('today'))->y;

		// try to guest the location based on the domain
		$inCuba = strrpos($profile->email, ".cu") == strlen($profile->email)-strlen(".cu");
		if(empty($profile->country) && $inCuba) $profile->country = "CU";

		// get the most accurate location possible
		$location = "";
		if($profile->city) $location = ucwords(strtolower($profile->city));
		elseif($profile->country=="US" && $profile->usstate) $location = $this->getStateNameFromCode($profile->usstate);
		elseif($profile->country=="CU" && $profile->province) $location = $this->getProvinceNameFromCode($profile->province);
		else $location = $this->getCountryNameFromCode($profile->country, $lang);
		$profile->location = substr($location, 0, 23);

		// get the person's full name
		$fullName = "{$profile->first_name} {$profile->middle_name} {$profile->last_name} {$profile->mother_name}";
		$profile->full_name = trim(preg_replace("/\s+/", " ", $fullName));

		// get the image of the person
		$profile->picture_internal = "";
		$profile->picture_public = "";
		if($profile->picture)
		{
			$di = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot = $di->get('path')['root'];
			$wwwhttp = $di->get('path')['http'];
			$profile->picture_internal = "$wwwroot/public/profile/{$profile->picture}.jpg";
			$profile->picture_public = "$wwwhttp/profile/{$profile->picture}.jpg";
			$profile->pictureURL = $profile->picture;
			$profile->picture = true;
		}

		// get the interests as a lowercase array
		$interests = preg_split('@,@', $profile->interests, NULL, PREG_SPLIT_NO_EMPTY);
		for($i=0;$i<count($interests);$i++) $interests[$i]=trim(strtolower($interests[$i]));
		$profile->interests = $interests;

		// remove whitespaces at the begining and ending of string fields
		foreach ($profile as $key=>$value) if( ! is_array($value)) $profile->$key = trim($value);

		// get the completion percentage of your profile
		$profile->completion = $this->getProfileCompletion($profile);

		// get the about me section
		if (empty($profile->about_me)) $profile->about_me = $this->profileToText($profile, $lang);

		// remove dangerous attributes from the response
		unset($profile->pin,$profile->insertion_date,$profile->last_access,$profile->active,$profile->last_update_date,$profile->updated_by_user,$profile->cupido,$profile->source,$profile->blocked);

		return $profile;
	}

	/**
	 * Get a US state code and return the name
	 *
	 * @author salvipascual
	 * @param String $stateCode
	 * @return String
	 */
	public function getStateNameFromCode($stateCode)
	{
		$states = array("AL" => "Alabama","AK" => "Alaska","AS" => "American Samoa","AZ" => "Arizona","AR" => "Arkansas","CA" => "California","CO" => "Colorado","CT" => "Connecticut","DE" => "Delaware","DC" => "Dist. of Columbia","FL" => "Florida","GA" => "Georgia","GU" => "Guam","HI" => "Hawaii","ID" => "Idaho","IL" => "Illinois","IN" => "Indiana","IA" => "Iowa","KS" => "Kansas","KY" => "Kentucky","LA" => "Louisiana","ME" => "Maine","MD" => "Maryland","MH" => "Marshall Islands","MA" => "Massachusetts","MI" => "Michigan","FM" => "Micronesia","MN" => "Minnesota","MS" => "Mississippi","MO" => "Missouri","MT" => "Montana","NE" => "Nebraska","NV" => "Nevada","NH" => "New Hampshire","NJ" => "New Jersey","NM" => "New Mexico","NY" => "New York","NC" => "North Carolina","ND" => "North Dakota","MP" => "Northern Marianas","OH" => "Ohio","OK" => "Oklahoma","OR" => "Oregon","PW" => "Palau","PA" => "Pennsylvania","PR" => "Puerto Rico","RI" => "Rhode Island","SC" => "South Carolina","SD" => "South Dakota","TN" => "Tennessee","TX" => "Texas","UT" => "Utah","VT" => "Vermont","VA" => "Virginia","VI" => "Virgin Islands","WA" => "Washington","WV" => "West Virginia","WI" => "Wisconsin","WY" => "Wyoming");
		return $states[strtoupper($stateCode)];
	}

	/**
	 * Get a Cuban province code and return its name
	 *
	 * @author salvipascual
	 * @param String $provinceCode
	 * @return String
	 */
	public function getProvinceNameFromCode($provinceCode)
	{
		$province = str_replace("_", " ", $provinceCode);
		$province = ucwords(strtolower($province));
		return $province;
	}

	/**
	 * Get a country name by its code and language
	 *
	 * @author salvipascual
	 * @param String $provinceCode
	 * @return String
	 */
	public function getCountryNameFromCode($countryCode, $lang="es")
	{
		// load the list of countries on a cache if not loaded yet
		if(empty($this->countries))
		{
			$connection = new Connection();
			$countries = $connection->deepQuery("SELECT * FROM countries;");
			foreach ($countries as $c) $this->countries[$c->code] = ["es" => $c->es, "en" => $c->en];
		}

		// return based on the language
		$countryCode = strtoupper($countryCode);
		if(isset($this->countries[$countryCode][$lang])) return $this->countries[$countryCode][$lang];
		elseif(isset($this->countries[$countryCode]['es'])) return $this->countries[$countryCode]['es'];
		else return $countryCode;
	}
}
