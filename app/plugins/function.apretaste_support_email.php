<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_apretaste_support_email($params, $template)
{
	// get the support email from the configs
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	$supportEmail = $di->get("config")["contact"]["support"];

	return $supportEmail;
}
