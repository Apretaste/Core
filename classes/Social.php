<?php

// group of functions for social purposes

class Social
{
	//
	// FUNCTIONS FOR PROFILE
	//

	/**
	 * Return description of profile as a paragraph, used for social services
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @param String $lang
	 * @return String
	 */
	public static function profileToText($profile, $lang="es")
	 {
		switch ($lang)
		{
			case 'es': return Social::profileToTextSpanish($profile);
			case 'en': return Social::profileToTextEnglish($profile);
			default: return Social::profileToTextSpanish($profile);
		}
	 }

	/**
	 * Return description of profile as a paragraph, in Spanish
	 *
	 * @author salvipascual
	 * @param Object $profile
	 * @return String
	 */
	private static function profileToTextSpanish($profile)
	{
		// get the age
		$age = empty($profile->year_of_birth) ? "" : date('Y') - $profile->year_of_birth;

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
		$province = ($profile->province) ? Social::getProvinceNameFromCode($profile->province) : false;

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : Social::getCountryNameFromCode($countryCode);
		$usstate = empty(trim($profile->usstate)) ? false : Social::getStateNameFromCode($profile->usstate);
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
			'CRISTIANISMO' => "soy cristian$genderFinalVowel",
			'PROTESTANTE' => "soy protestante"
		);
		$religion = empty($profile->religion) ? "" : $religions[$profile->religion];

		// create the message
		$message = "Hola";
		if ( ! empty(trim($profile->first_name))) $message .= ", mi nombre es " . ucfirst(strtolower(trim($profile->first_name)));
		if ( ! empty($age)) $message .= ", tengo $age años";
		if ( ! empty($gender)) $message .= ", soy $gender";
		if ( ! empty($religion)) $message .= ", $religion";
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
	private static function profileToTextEnglish($profile)
	{
		// get the age
		$age = empty($profile->year_of_birth) ? "" : date('Y') - $profile->year_of_birth;

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
		$province = ($profile->province) ? Social::getProvinceNameFromCode($profile->province) : false;

		// get the country, state and city
		$countryCode = strtoupper($profile->country);
		$country = empty(trim($profile->country)) ? false : Social::getCountryNameFromCode($countryCode, 'en');
		$usstate = empty(trim($profile->usstate)) ? false : Social::getStateNameFromCode($profile->usstate);
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
			'PROTESTANTE' => "I am Protestant",
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
	public static function getProfileCompletion($profile)
	{
		// get an array of the valid profile fields
		$keys = ["first_name","last_name","date_of_birth","gender","eyes","skin","body_type","hair","city","highest_school_level","occupation","marital_status","interests","picture","sexual_orientation","religion","country"];

		// count how much filled is the profile
		$counter = 0;
		if( ! empty($profile->usstate) ||  ! empty($profile->province)) $counter++;
		foreach ($keys as $key) {
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
	 *
	 * @param object $profile
	 * @param mixed $lang, language to return the profile text
	 *
	 * @return object
	 * */
	public static function prepareUserProfile($profile, $lang=false)
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
		elseif($profile->country=="US" && $profile->usstate) $location = Social::getStateNameFromCode($profile->usstate);
		elseif($profile->country=="CU" && $profile->province) $location = Social::getProvinceNameFromCode($profile->province);
		else $location = Social::getCountryNameFromCode($profile->country, $lang);
		$profile->location = substr($location, 0, 23);

		// get the person's full name
		$fullName = "{$profile->first_name} {$profile->middle_name} {$profile->last_name} {$profile->mother_name}";
		$profile->full_name = trim(preg_replace("/\s+/", " ", $fullName));

		// create paths to root and http
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];
		$wwwhttp = $di->get('path')['http'];

		// get the image of the person if exist
		if($profile->picture) {
			$profile->picture_internal = "$wwwroot/public/profile/{$profile->picture}.jpg";
			$profile->picture_public = "$wwwhttp/profile/{$profile->picture}.jpg";
			$profile->pictureURL = $profile->picture;
			$profile->picture = true;
		}else{
			$profile->picture_internal = "$wwwroot/public/images/user.jpg";
			$profile->picture_public = "$wwwhttp/images/user.jpg";
		}

		// get the extra images of the person if exist
		$profile->extra_pictures=json_decode($profile->extra_pictures,true);
		$profile->extraPictures_internal=array();
		$profile->extraPictures_public=array();
		$profile->extraPicturesURL=array();
		if(count($profile->extra_pictures)>0) {
			foreach ($profile->extra_pictures as $key => $picture) {
				$profile->extraPictures_internal[$key]= "$wwwroot/public/profile/{$picture}.jpg";
				$profile->extraPictures_public[$key]= "$wwwhttp/profile/{$picture}.jpg";
				$profile->extraPicturesURL[$key]= $picture;
			}
		}

		// get the interests as a lowercase array
		$interests = preg_split('@,@', $profile->interests, NULL, PREG_SPLIT_NO_EMPTY);
		for($i=0;$i<count($interests);$i++) $interests[$i]=trim(strtolower($interests[$i]));
		$profile->interests = $interests;

		// remove whitespaces at the begining and ending of string fields
		foreach ($profile as $key=>$value) if( ! is_array($value)) $profile->$key = trim($value);

		// get the completion percentage of your profile
		$profile->completion = Social::getProfileCompletion($profile);

		// get the about me section
		if (empty($profile->about_me)) $profile->about_me = Social::profileToText($profile, $lang);

		// remove dangerous attributes from the response
		unset($profile->pin,$profile->insertion_date,$profile->last_update_date,$profile->updated_by_user,$profile->cupido,$profile->source);

		return $profile;
	}

	/**
	 * Get a US state code and return the name
	 *
	 * @author salvipascual
	 * @param String $stateCode
	 * @return String
	 */
	public static function getStateNameFromCode($stateCode)
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
	public static function getProvinceNameFromCode($provinceCode)
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
	public static function getCountryNameFromCode($countryCode, $lang="es")
	{
		$countryCode = strtoupper($countryCode);
		$countries = Social::getCountries();
		if(isset($countries[$countryCode][$lang])) return $countries[$countryCode][$lang];
		elseif(isset($countries[$countryCode]['es'])) return $countries[$countryCode]['es'];
		else return $countryCode;
	}

	//
	// FUNCTIONS FOR CHAT
	//

	/**
	 * Return a list of Chats between $email1 & $email2
	 *
	 * @author salvipascual
	 * @param String $email1
	 * @param String $email2
	 * @param String $lastID , get all from this ID
	 * @param string $limit  , integer number of max rows
	 * @return array
	 */
	public static function chatsOpen($id)
	{
		// searching contacts of the current user
		$notes = Connection::query("
			SELECT A.*,B.text AS lastNote,B.read_date,B.from_user FROM (
				SELECT B.*, MAX(A.send_date) AS last
				FROM _note A JOIN person B
				ON A.to_user = B.id
				WHERE A.from_user = $id
				AND (A.active=10 OR A.active=11)
				GROUP BY B.id
				UNION
				SELECT B.*, MAX(A.send_date) AS last
				FROM _note A JOIN person B
				ON A.from_user = B.id
				WHERE A.to_user = $id
				AND (A.active=01 OR A.active=11)
				GROUP BY B.id) A JOIN _note B
				ON (B.to_user=A.id OR B.from_user=A.id) AND B.send_date=A.last
			ORDER BY last DESC");

		// add profiles to the list of notes
		$chats = []; $unique = [];
		foreach($notes as $n) {
			// do not allow repeated or empty rows
			if(empty($n->email) || in_array($n->email, $unique)) continue;
			$unique[] = $n->email;

			// create new chat object
			$chat = new stdClass();
			$chat->email = $n->email;
			$chat->last_sent = date('d/m/Y G:i',strtotime($n->last));
			$chat->last_note_user = $n->from_user;
			$chat->last_note_read = ($n->read_date != null && $n->from_user==$id) ? true : false;
			$chat->last_note_readDate = ($chat->last_note_read) ? date('d/m/Y G:i',strtotime($n->read_date)) : "";
			$chat->last_note = (strlen($n->lastNote) > 30) ? substr($n->lastNote,0,30).'...' : $n->lastNote;
			$chat->last_note = ($n->from_user!=$id && $n->read_date==null) ? "<strong>$chat->last_note</strong>" : $chat->last_note;
			$chat->profile = Social::prepareUserProfile($n);
			$chat->cantidad = []; // @TODO delete
			$chats[] = $chat;
		}

		return $chats;
	}

	/**
	 *Ocults a conversation for a user, with another user
	 *
	 *@param String $from_user
	 *@param String $to_user
	 */
public static function chatOcult($from_user,$to_user){
	Connection::query("
	 START TRANSACTION;
	 UPDATE _note SET active=01 WHERE from_user=$from_user AND to_user=$to_user AND active=11;
	 UPDATE _note SET active=10 WHERE from_user=$to_user AND to_user=$from_user AND active=11;
	 UPDATE _note SET active=00 WHERE (from_user=$from_user AND to_user=$to_user AND active=10);
	 UPDATE _note SET active=00 WHERE (from_user=$to_user AND to_user=$from_user AND active=01);
	 COMMIT
	 ");
}
	/**
	 * Return a list of Chats between $email1 & $email2
	 *
	 * @author salvipascual
	 * @param String $email1
	 * @param String $email2
	 * @param String $lastID , get all from this ID
	 * @param string $limit  , integer number of max rows
	 * @return array
	 */
	public static function chatConversation($yourId, $friendId, $lastID=0, $limit=20)
	{
		// if a last ID is passed, do not cut the result based on the limit
		$lastID = ($lastID > 0) ? "" : "AND A.id > $lastID";

		// retrieve conversation between users
		$notes = Connection::query("
			SELECT * FROM (
				SELECT A.id AS note_id, A.text, A.send_date as sent, A.read_date as `read`, A.from_user, B.*
				FROM _note A LEFT JOIN person B
				ON A.from_user = B.id
				WHERE from_user = $yourId AND to_user = $friendId
				AND NOT (A.active=00 OR A.active=01)
				$lastID
				UNION
				SELECT A.id AS note_id, A.text, A.send_date as sent, A.read_date as `read`, A.from_user, B.*
				FROM _note A LEFT JOIN person B
				ON A.from_user = B.id
				WHERE from_user = $friendId AND to_user = $yourId
				AND NOT (A.active=00 OR A.active=10)
				$lastID) C
			ORDER BY sent DESC LIMIT 20");

		// mark the other person notes as unread
		if($notes) {
			$lastNoteID = end($notes)->note_id;
			Connection::query("
				UPDATE _note
				SET read_date = CURRENT_TIMESTAMP
				WHERE read_date is NULL
				AND from_user = $friendId
				AND to_user >= $yourId");
		}

		// format profile
		$chats = [];
		foreach($notes as $n) {
			$n->readed = ($n->read!=null and $n->from_user==$yourId)?true:false;
			$n->sender = ($n->from_user==$yourId)?"me":"you";
			$chats[] = Social::prepareUserProfile($n);
		}
		return $chats;
	}

	/**
	 * Get the arrays of countries
	 */
	private static function getCountries() {
		return [
			"AD" => ["es"=>"Andorra", "en"=>"Andorra"],
			"AE" => ["es"=>"Emiratos Arabes Unidos", "en"=>"United Arab Emirates"],
			"AF" => ["es"=>"Afganistan", "en"=>"Afghanistan"],
			"AG" => ["es"=>"Antigua y Barbuda", "en"=>"Antigua and Barbuda"],
			"AL" => ["es"=>"Albania", "en"=>"Albania"],
			"AM" => ["es"=>"Armenia", "en"=>"Armenia"],
			"AO" => ["es"=>"Angola", "en"=>"Angola"],
			"AR" => ["es"=>"Argentina", "en"=>"Argentina"],
			"AT" => ["es"=>"Austria", "en"=>"Austria"],
			"AU" => ["es"=>"Australia", "en"=>"Australia"],
			"AZ" => ["es"=>"Azerbaiyan", "en"=>"Azerbaijan"],
			"BA" => ["es"=>"Bosnia y Herzegovina", "en"=>"Bosnia and Herzegovina"],
			"BB" => ["es"=>"Barbados", "en"=>"Barbados"],
			"BD" => ["es"=>"Bangladesh", "en"=>"Bangladesh"],
			"BE" => ["es"=>"Belgica", "en"=>"Belgium"],
			"BF" => ["es"=>"Burkina Faso", "en"=>"Burkina Faso"],
			"BG" => ["es"=>"Bulgaria", "en"=>"Bulgaria"],
			"BH" => ["es"=>"Bahrein", "en"=>"Bahrain"],
			"BI" => ["es"=>"Burundi", "en"=>"Burundi"],
			"BJ" => ["es"=>"Benin", "en"=>"Benin"],
			"BN" => ["es"=>"Brunei Darussalam", "en"=>"Brunei Darussalam"],
			"BO" => ["es"=>"Bolivia", "en"=>"Bolivia"],
			"BR" => ["es"=>"Brasil", "en"=>"Brazil"],
			"BS" => ["es"=>"Bahamas", "en"=>"Bahamas"],
			"BT" => ["es"=>"Bhutan", "en"=>"Bhutan"],
			"BW" => ["es"=>"Botswana", "en"=>"Botswana"],
			"BY" => ["es"=>"Belarus", "en"=>"Belarus"],
			"BZ" => ["es"=>"Belice", "en"=>"Belize"],
			"CA" => ["es"=>"Canada", "en"=>"Canada"],
			"CD" => ["es"=>"Republica Democratica del Congo", "en"=>"Democratic Republic of the Congo"],
			"CF" => ["es"=>"Republica Centroafricana", "en"=>"Central African Republic"],
			"CG" => ["es"=>"Congo", "en"=>"Congo"],
			"CH" => ["es"=>"Suiza", "en"=>"Switzerland"],
			"CI" => ["es"=>"Costa de Marfil", "en"=>"Cote d Ivoire"],
			"CK" => ["es"=>"Islas Cook", "en"=>"Cook Islands"],
			"CL" => ["es"=>"Chile", "en"=>"Chile"],
			"CM" => ["es"=>"Camerun", "en"=>"Cameroon"],
			"CN" => ["es"=>"China", "en"=>"China"],
			"CO" => ["es"=>"Colombia", "en"=>"Colombia"],
			"CR" => ["es"=>"Costa Rica", "en"=>"Costa Rica"],
			"CU" => ["es"=>"Cuba", "en"=>"Cuba"],
			"CV" => ["es"=>"Cabo Verde", "en"=>"Cabo Verde"],
			"CY" => ["es"=>"Chipre", "en"=>"Cyprus"],
			"CZ" => ["es"=>"Chequia", "en"=>"Czechia"],
			"DE" => ["es"=>"Alemania", "en"=>"Germany"],
			"DJ" => ["es"=>"Djibouti", "en"=>"Djibouti"],
			"DK" => ["es"=>"Dinamarca", "en"=>"Denmark"],
			"DM" => ["es"=>"Dominica", "en"=>"Dominica"],
			"DO" => ["es"=>"Republica Dominicana", "en"=>"Dominican Republic"],
			"DZ" => ["es"=>"Argelia", "en"=>"Algeria"],
			"EC" => ["es"=>"Ecuador", "en"=>"Ecuador"],
			"EE" => ["es"=>"Estonia", "en"=>"Estonia"],
			"EG" => ["es"=>"Egipto", "en"=>"Egypt"],
			"ER" => ["es"=>"Eritrea", "en"=>"Eritrea"],
			"ES" => ["es"=>"Espana", "en"=>"Spain"],
			"ET" => ["es"=>"Etiopia", "en"=>"Ethiopia"],
			"FI" => ["es"=>"Finlandia", "en"=>"Finland"],
			"FJ" => ["es"=>"Fiji", "en"=>"Fiji"],
			"FM" => ["es"=>"Micronesia", "en"=>"Micronesia"],
			"FR" => ["es"=>"Francia", "en"=>"France"],
			"GA" => ["es"=>"Gabon", "en"=>"Gabon"],
			"GB" => ["es"=>"Reino Unido", "en"=>"United Kingdom"],
			"GD" => ["es"=>"Granada", "en"=>"Grenada"],
			"GE" => ["es"=>"Georgia", "en"=>"Georgia"],
			"GH" => ["es"=>"Ghana", "en"=>"Ghana"],
			"GM" => ["es"=>"Gambia", "en"=>"Gambia"],
			"GN" => ["es"=>"Guinea", "en"=>"Guinea"],
			"GQ" => ["es"=>"Guinea Ecuatorial", "en"=>"Equatorial Guinea"],
			"GR" => ["es"=>"Grecia", "en"=>"Greece"],
			"GT" => ["es"=>"Guatemala", "en"=>"Guatemala"],
			"GW" => ["es"=>"Guinea-Bissau", "en"=>"Guinea-Bissau"],
			"GY" => ["es"=>"Guyana", "en"=>"Guyana"],
			"HN" => ["es"=>"Honduras", "en"=>"Honduras"],
			"HR" => ["es"=>"Croacia", "en"=>"Croatia"],
			"HT" => ["es"=>"Haiti", "en"=>"Haiti"],
			"HU" => ["es"=>"Hungria", "en"=>"Hungary"],
			"ID" => ["es"=>"Indonesia", "en"=>"Indonesia"],
			"IE" => ["es"=>"Irlanda", "en"=>"Ireland"],
			"IL" => ["es"=>"Israel", "en"=>"Israel"],
			"IN" => ["es"=>"India", "en"=>"India"],
			"IQ" => ["es"=>"Iraq", "en"=>"Iraq"],
			"IR" => ["es"=>"Iran", "en"=>"Iran"],
			"IS" => ["es"=>"Islandia", "en"=>"Iceland"],
			"IT" => ["es"=>"Italia", "en"=>"Italy"],
			"JM" => ["es"=>"Jamaica", "en"=>"Jamaica"],
			"JO" => ["es"=>"Jordania", "en"=>"Jordan"],
			"JP" => ["es"=>"Japon", "en"=>"Japan"],
			"KE" => ["es"=>"Kenya", "en"=>"Kenya"],
			"KG" => ["es"=>"Kirguistan", "en"=>"Kyrgyzstan"],
			"KH" => ["es"=>"Camboya", "en"=>"Cambodia"],
			"KI" => ["es"=>"Kiribati", "en"=>"Kiribati"],
			"KM" => ["es"=>"Comoras", "en"=>"Comoros"],
			"KN" => ["es"=>"Saint Kitts y Nevis", "en"=>"Saint Kitts and Nevis"],
			"KP" => ["es"=>"Republica Popular Democratica de Corea", "en"=>"Democratic Peoples Republic of Korea"],
			"KR" => ["es"=>"Republica de Corea", "en"=>"Republic of Korea"],
			"KW" => ["es"=>"Kuwait", "en"=>"Kuwait"],
			"KZ" => ["es"=>"Kazajstan", "en"=>"Kazakhstan"],
			"LA" => ["es"=>"Republica Democratica Popular Lao", "en"=>"Lao Peoples Democratic Republic"],
			"LB" => ["es"=>"Libano", "en"=>"Lebanon"],
			"LC" => ["es"=>"Santa Lucia", "en"=>"Saint Lucia"],
			"LK" => ["es"=>"Sri Lanka", "en"=>"Sri Lanka"],
			"LR" => ["es"=>"Liberia", "en"=>"Liberia"],
			"LS" => ["es"=>"Lesotho", "en"=>"Lesotho"],
			"LT" => ["es"=>"Lituania", "en"=>"Lithuania"],
			"LU" => ["es"=>"Luxemburgo", "en"=>"Luxembourg"],
			"LV" => ["es"=>"Letonia", "en"=>"Latvia"],
			"LY" => ["es"=>"Libia", "en"=>"Libya"],
			"MA" => ["es"=>"Marruecos", "en"=>"Morocco"],
			"MC" => ["es"=>"Monaco", "en"=>"Monaco"],
			"MD" => ["es"=>"Republica de Moldova", "en"=>"Republic of Moldova"],
			"ME" => ["es"=>"Montenegro", "en"=>"Montenegro"],
			"MG" => ["es"=>"Madagascar", "en"=>"Madagascar"],
			"MH" => ["es"=>"Islas Marshall", "en"=>"Marshall Islands"],
			"MK" => ["es"=>"ex Republica Yugoslava de Macedonia", "en"=>"The former Yugoslav Republic of Macedonia"],
			"ML" => ["es"=>"Mali", "en"=>"Mali"],
			"MM" => ["es"=>"Myanmar", "en"=>"Myanmar"],
			"MN" => ["es"=>"Mongolia", "en"=>"Mongolia"],
			"MR" => ["es"=>"Mauritania", "en"=>"Mauritania"],
			"MT" => ["es"=>"Malta", "en"=>"Malta"],
			"MU" => ["es"=>"Mauricio", "en"=>"Mauritius"],
			"MV" => ["es"=>"Maldivas", "en"=>"Maldives"],
			"MW" => ["es"=>"Malawi", "en"=>"Malawi"],
			"MX" => ["es"=>"Mexico", "en"=>"Mexico"],
			"MY" => ["es"=>"Malasia", "en"=>"Malaysia"],
			"MZ" => ["es"=>"Mozambique", "en"=>"Mozambique"],
			"NA" => ["es"=>"Namibia", "en"=>"Namibia"],
			"NE" => ["es"=>"Niger", "en"=>"Niger"],
			"NG" => ["es"=>"Nigeria", "en"=>"Nigeria"],
			"NI" => ["es"=>"Nicaragua", "en"=>"Nicaragua"],
			"NL" => ["es"=>"Paises Bajos", "en"=>"Netherlands"],
			"NO" => ["es"=>"Noruega", "en"=>"Norway"],
			"NP" => ["es"=>"Nepal", "en"=>"Nepal"],
			"NR" => ["es"=>"Nauru", "en"=>"Nauru"],
			"NU" => ["es"=>"Niue", "en"=>"Niue"],
			"NZ" => ["es"=>"Nueva Zelandia", "en"=>"New Zealand"],
			"OM" => ["es"=>"Oman", "en"=>"Oman"],
			"PA" => ["es"=>"Panama", "en"=>"Panama"],
			"PE" => ["es"=>"Peru", "en"=>"Peru"],
			"PG" => ["es"=>"Papua Nueva Guinea", "en"=>"Papua New Guinea"],
			"PH" => ["es"=>"Filipinas", "en"=>"Philippines"],
			"PK" => ["es"=>"Pakistan", "en"=>"Pakistan"],
			"PL" => ["es"=>"Polonia", "en"=>"Poland"],
			"PT" => ["es"=>"Portugal", "en"=>"Portugal"],
			"PW" => ["es"=>"Palau", "en"=>"Palau"],
			"PY" => ["es"=>"Paraguay", "en"=>"Paraguay"],
			"QA" => ["es"=>"Qatar", "en"=>"Qatar"],
			"RO" => ["es"=>"Rumania", "en"=>"Romania"],
			"RS" => ["es"=>"Serbia", "en"=>"Serbia"],
			"RU" => ["es"=>"Federacion de Rusia", "en"=>"Russian Federation"],
			"RW" => ["es"=>"Rwanda", "en"=>"Rwanda"],
			"SA" => ["es"=>"Arabia Saudita", "en"=>"Saudi Arabia"],
			"SB" => ["es"=>"Islas Salomon", "en"=>"Solomon Islands"],
			"SC" => ["es"=>"Seychelles", "en"=>"Seychelles"],
			"SD" => ["es"=>"Sudan", "en"=>"Sudan"],
			"SE" => ["es"=>"Suecia", "en"=>"Sweden"],
			"SG" => ["es"=>"Singapur", "en"=>"Singapore"],
			"SI" => ["es"=>"Eslovenia", "en"=>"Slovenia"],
			"SK" => ["es"=>"Eslovaquia", "en"=>"Slovakia"],
			"SL" => ["es"=>"Sierra Leona", "en"=>"Sierra Leone"],
			"SM" => ["es"=>"San Marino", "en"=>"San Marino"],
			"SN" => ["es"=>"Senegal", "en"=>"Senegal"],
			"SO" => ["es"=>"Somalia", "en"=>"Somalia"],
			"SR" => ["es"=>"Suriname", "en"=>"Suriname"],
			"SS" => ["es"=>"Sudan del Sur", "en"=>"South Sudan"],
			"ST" => ["es"=>"Santo Tome y Principe", "en"=>"Sao Tome and Principe"],
			"SV" => ["es"=>"El Salvador", "en"=>"El Salvador"],
			"SY" => ["es"=>"Republica Arabe Siria", "en"=>"Syrian Arab Republic"],
			"SZ" => ["es"=>"Swazilandia", "en"=>"Swaziland"],
			"TD" => ["es"=>"Chad", "en"=>"Chad"],
			"TG" => ["es"=>"Togo", "en"=>"Togo"],
			"TH" => ["es"=>"Tailandia", "en"=>"Thailand"],
			"TJ" => ["es"=>"Tayikistan", "en"=>"Tajikistan"],
			"TL" => ["es"=>"Timor-Leste", "en"=>"Timor-Leste"],
			"TM" => ["es"=>"Turkmenistan", "en"=>"Turkmenistan"],
			"TN" => ["es"=>"Tunez", "en"=>"Tunisia"],
			"TO" => ["es"=>"Tonga", "en"=>"Tonga"],
			"TR" => ["es"=>"Turquia", "en"=>"Turkey"],
			"TT" => ["es"=>"Trinidad y Tabago", "en"=>"Trinidad and Tobago"],
			"TV" => ["es"=>"Tuvalu", "en"=>"Tuvalu"],
			"TZ" => ["es"=>"Republica Unida de Tanzania", "en"=>"United Republic of Tanzania"],
			"UA" => ["es"=>"Ucrania", "en"=>"Ukraine"],
			"UG" => ["es"=>"Uganda", "en"=>"Uganda"],
			"US" => ["es"=>"Estados Unidos", "en"=>"United States"],
			"UY" => ["es"=>"Uruguay", "en"=>"Uruguay"],
			"UZ" => ["es"=>"Uzbekistan", "en"=>"Uzbekistan"],
			"VC" => ["es"=>"San Vicente y las Granadinas", "en"=>"Saint Vincent and the Grenadines"],
			"VE" => ["es"=>"Venezuela", "en"=>"Venezuela"],
			"VN" => ["es"=>"Viet Nam", "en"=>"Viet Nam"],
			"VU" => ["es"=>"Vanuatu", "en"=>"Vanuatu"],
			"WS" => ["es"=>"Samoa", "en"=>"Samoa"],
			"XX" => ["es"=>"Otro", "en"=>"Other"],
			"YE" => ["es"=>"Yemen", "en"=>"Yemen"],
			"ZA" => ["es"=>"Sudafrica", "en"=>"South Africa"],
			"ZM" => ["es"=>"Zambia", "en"=>"Zambia"],
			"ZW" => ["es"=>"Zimbabwe", "en"=>"Zimbabwe"]
		];
	}
}
