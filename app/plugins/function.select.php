<?php

/**
 * Smarty plugin
 *
 * @package	Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_select($params, $template)
{
	$selected = $params["selected"];
	$options = json_decode($params["options"]); // json {href, caption}

	// create select for the web
	$di = \Phalcon\DI\FactoryDefault::getDefault();
	if($di->get('environment') == "web")
	{
		// create select for the web
		$select = '<select class="dropdown" onchange="apretaste.onSelect(this.value);">';
		if(empty($selected)) $select .= '<option value=""></option>';
		foreach($options as $option) {
			$optionSelected = (strtoupper($option->caption) == strtoupper($selected)) ? "selected='selected'" : "";
			$select .= "<option value='{$option->href}' $optionSelected>{$option->caption}</option>";
		}
		$select .= '</select>';
	}
	// create select for the email and app
	else
	{
		// include the functions to create links and separators
		$wwwroot = $di->get('path')['root'];
		require_once "$wwwroot/app/plugins/function.link.php";
		require_once "$wwwroot/app/plugins/function.separator.php";

		// create select for the email and app
		$i = 0; $len = count($options)-1;
		$select = '<small>';
		foreach($options as $option)
		{
			// get the selected option
			if(strtoupper($option->caption) == strtoupper($selected)) $select .= "<b>{$option->caption}</b>";

			// get a selectable option
			else $select .= smarty_function_link(["href"=>$option->href, "caption"=>$option->caption, "wait"=>"false"], $template);

			// add the separator between each value (unless is the last one)
			if ($i != $len) $select .= " ".smarty_function_separator([], $template)." ";
			$i++;
		}
		$select .= '</small>';
	}

	// return the HTML code
	return $select;
}
