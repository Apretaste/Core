<?php
//MySQL Config
/*$mysqlhost= "localhost";
 $database = "apretaste";
 $username = "core";
 $mysqpassword = "core";*/
 

//Prostgress Config
/*public $host = "216.224.175.73";
 public $port = "5432";
 public $dbname = "apretaste";
 public $user = "apretaste";
 public $password = "UncleSalviAdventures";*/


//MySQL Coneection

	//MySQL Coneection
	/*try
	 {*/
		 $mysqlConn = new PDO("mysql:host=localhost;dbname=apretaste", "core", "core");
		 // set the PDO error mode to exception
		/* $mysqlConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		 echo "Connected to MySQL successfully";
	 }
	 catch(PDOException $e)
	 {
	 	echo "Connection to MySQL failed: " . $e->getMessage();
	 }*/

	/*$pgConn = pg_connect("host=216.224.175.73 port=5432 dbname=apretaste user=apretaste password=UncleSalviAdventures");
	 $result = pg_query($pgConn, "SELECT * FROM authors");
	 $pgCount = pg_affected_rows($result);
	 print_r($pgCount);
	 exit;*/
	
	//PostgresSQL Connection
	try
	{
		$pgConn = new PDO("pgsql:host=216.224.175.73; port=5432; dbname=apretaste; user=apretaste; password=UncleSalviAdventures");
		//$pgConn = new PDO("pgsql:dbname=$dbname;host=$host;user=$user;password=$password");
		// set the PDO error mode to exception
		$pgConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		echo "Connected to PostgresSQL successfully";
		
		//Querying PostgresSQl 
		$pgQuery = $pgConn->prepare("SELECT email, name, phones, birthdate, sex, ocupation, state, city, sentimental, interest, picture, eyes, skin, hair, school_level, about, credit FROM authors, credit");
		$pgQuery->execute();
		$pgCount = $pgQuery->rowCount();		
		$pgResult = $pgQuery->fetch();
		
		//Preparing the data
		//Preparing Phone Numbers
		$phone = NULL;
		$cellphyone = NULL;
		$phoneString = $pgResult[2];
		if($phoneString != "" && $phoneString != NULL)
		{
			$phoneArr = explode(",", $phoneString);
			foreach ($phoneArr as $phoneInfo)
			{
				$trim = trim($phoneInfo);
				$fistChar = substr($trim, 0);
				if($fistChar.equal("5"))
					$cellphyone = $trim;
				else 
					$phone = $trim;
			}
		}
		//End Preparing Phone Numbers
		//End Preparing data
		
		$mysqlQuery = $mysqlConn->prepare("INSERT INTO person (email) VALUE (:email)");
		$mysqlQuery->bindParam(':email', $pgResult[0]);
		$mysqlQuery->execute();
		
		/*for($i = 0; $i < $pgCount; $i++)
		{
			$pgResult = $pgQuery->fetch();
			$mysqlQuery = $mysqlConn->prepare("INSERT INTO person (email) VALUE (:email)");
			$mysqlQuery->bindParam(':email', $pgResult[0]);
			$mysqlQuery->execute();
		}*/							
	}
	catch (PDOException $e)
	{
		echo "Connection to PostgresSQL failed: " . $e->getMessage();
	}