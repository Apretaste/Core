<?php

use Phalcon\Mvc\Router;

// Create the router
$router = new Router();

// create route to logout
$router->add("/logout", ["controller" => "login", "action" => "logout"]);

$router->handle();
