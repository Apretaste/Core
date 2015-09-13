<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_noimage($params, $template)
{
	$width =  isset($params["width"]) ? $params["width"] : "100";
	$height =  isset($params["height"]) ? $params["height"] : "100";
	$text = isset($params["text"]) ? strtoupper($params["text"]) : "NO FOTO";

	return "
		<table>
			<tr>
				<td width='$width' height='$height' bgcolor='#F2F2F2' align='center' valign='middle'>
					<div style='width:{$width}px; color:gray;'>
						<small>$text</small>
					</div>
				</td>
			</tr>
		</table>";
}
