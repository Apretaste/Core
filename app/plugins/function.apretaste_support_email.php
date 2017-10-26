<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_apretaste_support_email($params, $template)
{
	$utils = new Utils();
	$supportEmail = $utils->getSupportEmailAddress();
	return $supportEmail;
}
