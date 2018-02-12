<?php

// Exclusively used for index.php functionality (not using a library) and unit tests
spl_autoload_register(function ($n) {

	$di = \Phalcon\DI\FactoryDefault::getDefault();
	$wwwroot = $di->get('path')['root'];

	$path = $wwwroot . '/lib/' . str_replace('\\', '/', $n) . '.php';

	if (!$path) {
		return;
	}

	if (!is_file($path)) {
		return;
	}

	require_once $path;

	if (!class_exists($n) && !interface_exists($n)) {
		echo "$n not exists in $path\n";
		die(1);
	}
});
