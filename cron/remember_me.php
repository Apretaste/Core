<?php

/**
 * Apretaste Remember Me cron job/task
 * 
 * @version 1.0
 */
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Config\Adapter\Ini as ConfigIni;

// set the date to come in Spanish
setlocale(LC_TIME, "es_ES");

// include composer
include_once "../vendor/autoload.php";

include_once "../classes/Service.php";
include_once "../classes/Utils.php";
include_once "../classes/Render.php";
include_once "../classes/Response.php";
include_once "../classes/Connection.php";

echo "[INFO] RmemberMe cron job started at " . date("Y-m-d h:i:s") . "\n ";

// Register autoLoader for Analytics

$loaderAnalytics = new Loader();
$loaderAnalytics->registerDirs(array(
        '../classes/',
        '../app/controllers/'
))->register();

// Create Run DI
$di = new FactoryDefault();

// Creating the global path to the root folder
$di->set('path', function  ()
{
    return array(
            "root" => dirname(__DIR__),
            "http" => "http://localhost"
    );
});

// Making the config global
$di->set('config', function  ()
{
    return new ConfigIni('../configs/config.ini');
});

// Setup the view component for Analytics
$di->set('view', function  ()
{
    $view = new View();
    $view->setLayoutsDir('../layouts/');
    $view->setViewsDir('../app/views/');
    return $view;
});

// Setup the database service
$config = $di->get('config');
$di->set('db', 
        function  () use( $config)
        {
            return new \Phalcon\Db\Adapter\Pdo\Mysql(
                    array(
                            "host" => $config['database']['host'],
                            "username" => $config['database']['user'],
                            "password" => $config['database']['password'],
                            "dbname" => $config['database']['database']
                    ));
        });

// get the environment
$di->set('environment', function  () use( $config)
{
    if (isset($config['global']['environment']))
        return $config['global']['environment'];
    else
        return "production";
});

// get connection params from the file
$configs = parse_ini_file(__DIR__ . "/../configs/config.ini", true)['database'];

echo "[INFO] Connecting to database ... \n ";

// Create connection
$conn = new mysqli($configs['host'], $configs['user'], $configs['password'], $configs['database']);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Helper for search persons, with active > 0

$sql_persons = "SELECT active, email, datediff(CURRENT_DATE,(SELECT min(request_time) FROM utilization WHERE utilization.requestor = person.email)) AS last_usage_days FROM person WHERE active > 0";

echo "[INFO] Searching between 30 and 60, udpate active = 2 ... \n ";

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days >=30 AND last_usage_days <= 60) OR last_usage_days is null and subq.active < 2;");

$service = new Service();
$service->showAds = true;
$render = new Render();
$response = new Response();
$response->setResponseSubject('Hace tiempo no usas Apretaste!');
$email = new Email();

$c = 1;

if ($result !== false) while ($row = $result->fetch_assoc()) {
    echo "[INFO] Send email to user {$row['email']}\n";
    $response->createFromTemplate('remember_me_3060.tpl', array());
    $response->internal = true;
    $html = $render->renderHTML($service, $response);
    $email->sendEmail($row['email'], 'Hace tiempo no usas Apretaste!', $html);
    $conn->query("UPDATE person SET active = 2 WHERE email = '{$row['email']}';");
    $conn->query("INSERT INTO utilization (service,requestor,request_time) VALUES ('rememberme', '{$row['email']}', CURRENT_TIMESTAMP);");
}

echo "[INFO] Searching between 60 and 90, udpate active = 3 ...\n";

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days > 60 AND last_usage_days <= 90) and subq.active < 3;");

$c = 1;
if ($result !== false) while ($row = $result->fetch_assoc()) {
    echo "[INFO] Send email to user {$row['email']}\n";
    $response->createFromTemplate('remember_me_6090.tpl', array());
    $response->internal = true;
    $html = $render->renderHTML($service, $response);
    $email->sendEmail($row['email'], 'Hace tiempo no usas Apretaste!', $html);
    $conn->query("INSERT INTO utilization (service,requestor,request_time) VALUES ('rememberme', '{$row['email']}', CURRENT_TIMESTAMP);");
    $conn->query("UPDATE person SET active = 3 WHERE email = '{$row['email']}';");
}

echo "[INFO] Searching more than 90, udpate active = 0 \n";

$result = $conn->query("SELECT * FROM ($sql_persons) AS subq WHERE (last_usage_days > 90) and subq.active < 3;");

$c = 1;
if ($result !== false) while ($row = $result->fetch_assoc()) {
    echo "[INFO] Desactivate the user {$row['email']}\n";
    
    $conn->query("UPDATE person SET active = 0 WHERE email = '{$row['email']}';");
    
    echo "[INFO] Unsubscribe the user {$row['email']}\n";
    
    $service->utils->unsubscribeFromEmailList($row['email']);
}