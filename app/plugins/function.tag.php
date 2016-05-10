<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_tag($params, $template)
{
	// get params
	$caption = strtoupper($params["caption"]);
	$color = isset($params["color"]) ? $params["color"] : "white";
	$bgcolor = isset($params["color"]) ? $params["color"] : "#202020";

	// create the tag
	return "<nobr><span style='white-space:nowrap; padding:1px 8px; margin-top:5px; background-color:$bgcolor; color:$color; border-radius:5px; font-size:12px;'>$caption</span></nobr>";
}
