<?php

// get connection params from the file
$configs = parse_ini_file(__DIR__."/../configs/config.ini", true)['database'];

// Create connection
$conn = new mysqli($configs['host'], $configs['user'], $configs['password'], $configs['database']);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$result = $conn->query("select email from person");

$c=1;
while($row = $result->fetch_assoc())
{
	$email = $row['email'];

	// create a unique username
	$username = explode("@", $email)[0]; // get only the name part
	$username = preg_replace('/[^A-Za-z0-9]/', '', $username); // remove special chars
	$username = substr($username, 0, 5); // get the first 5 chars
	
	$res = $conn->query("SELECT username as users FROM person WHERE username LIKE '$username%'");
	if($res->num_rows > 0) $username = $username . $res->num_rows;

	$conn->query("UPDATE person SET username='$username' WHERE email='$email'");

	echo "\n" . $c . "/" . $result->num_rows . "\n";
	$c++;
}

