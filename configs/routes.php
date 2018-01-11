<?php

use Phalcon\Mvc\Router;

// Create the router
$router = new Router();

// create route to main page
$router->add("/bienvenido", ["controller"=>"index", "action"=>"index", "lang"=>"es"]);
$router->add("/welcome", ["controller"=>"index", "action"=>"index", "lang"=>"en"]);
$router->add("/team", ["controller"=>"index", "action"=>"team"]);

// create route to logout
$router->add("/logout", ["controller" => "login", "action" => "logout"]);

$router->handle();
