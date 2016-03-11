<?php

// get connection params from the file
$configs = parse_ini_file(__DIR__ . "/../configs/config.ini", true)['database'];

// Create connection
$conn = new mysqli($configs['host'], $configs['user'], $configs['password'], $configs['database']);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Helper for search persons, with active > 0

$sql_persons = "SELECT email, datediff(CURRENT_DATE,(SELECT min(request_time) FROM utilization WHERE utilization.requestor = person.email)) AS last_usage_days FROM person WHERE active > 0";

// Searching between 30 and 60, udpate active = 2

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days >=30 AND last_usage_days <= 60) OR last_usage_days is null;");

$c = 1;
while ($row = $result->fetch_assoc()) {
    $email = new Email();
    $response = new Response();
    $response->internal = true;
    $response->setResponseSubject('Hace tiempo no usas Apretaste!');
    $response->createFromTemplate('message.tpl', $content);
    $email->sendEmail($row['email'], 'Hace tiempo no usas Apretaste!', '');
    $conn->query("UPDATE person SET active = 2 WHERE email = '{$row['email']}';");
}

// Searching between 60 and 90, udpate active = 1

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days > 60 AND last_usage_days <= 90);");

$c = 1;
while ($row = $result->fetch_assoc()) {
    $email = new Email();
    $email->sendEmail($row['email'], 'Hace tiempo no usas Apretaste!', '<h1>Hace tiempo no usas Apretaste!</h1><p>En 30 dias tu cuenta se desactivara</p>');
    $conn->query("UPDATE person SET active = 1 WHERE email = '{$row['email']}';");
}

// Searching more than 90, udpate active = 0

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days > 90);");

$c = 1;
while ($row = $result->fetch_assoc()) {
    $conn->query("UPDATE person SET active = 0 WHERE email = '{$row['email']}';");
}