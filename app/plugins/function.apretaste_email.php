<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_apretaste_email($params, $template)
{
	// get a valid apretaste email address
	$utils = new Utils();
	$validEmailAddress = $utils->getValidEmailAddress();

	return $validEmailAddress;
}
