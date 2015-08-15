<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_emailbox($params, $template)
{
	// get params
	$title = $params["title"];
	$from = $params["from"];
	$subject = $params["subject"];

	// get the body and width if exist
	$body = isset($params["body"]) ? $params["body"] : "";
	$width = isset($params["width"]) ? $params["width"] : "250";

	// get a valid apretaste email address
	$utils = new Utils();
	$to = $utils->getValidEmailAddress();

	// construct params for the emailbox
	$leftColWidth = $width*50/397;
	$rightColWidth = $width*315/397;
	$titleWidth = $width*315/397;

	return "
	<table style='font-size: 12px;font-family: Verdana; border: 1px solid gray; background:#eeeeee; width:{$width}px;margin-left: 8px;' cellspacing='0' cellpadding='0' width='$width'>
		<tr style='background: #4c9ed9;'>
			<td style='padding: 5px;' align='left' colspan='2'>
				<table width='$width' cellspacing='0' cellpadding='0' style='margin: 0px;'>
					<tr>
						<td style='font-family: Verdana; color: white; font-weight: bold;font-size: 12px;' width='$titleWidth'>{$title}</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td style='padding: 5px;' align='right' width='$leftColWidth'>De:</td>
			<td align='left'>
				<table cellspacing='0' cellpadding='0' style='font-size: 12px;font-family: Lucida console; margin: 5px;background: white; color:black; padding: 5px; border: 1px solid gray; width:{$rightColWidth}px;'>
				<tr><td>$from</td></tr>
				</table>
			</td>
		</tr>
		<tr>
			<td style='padding: 5px;' align='right' width='$leftColWidth'>Para:</td>
			<td align='left'>
				<table cellspacing='0' cellpadding='0' style='font-size: 12px;font-family: Lucida console; margin: 5px;background: white; color:black; width:350px; padding: 5px; border: 1px solid gray;width:{$rightColWidth}px;'>
				<tr><td>$to</td></tr>
				</table>
			</td>
		</tr>
		<tr>
			<td style='padding: 5px;' align='right' width='$leftColWidth'>Asunto:</td>
			<td align='left'>
				<table cellspacing='0' cellpadding='0' style='font-size: 12px;font-family: Lucida console; margin: 5px; background: white; color:black;  padding: 5px; border: 1px solid gray;width:{$rightColWidth}px;' type='text'>
				<tr><td>$subject</td></tr>
				</table>
			</td>
		</tr>
		<tr>
			<td valign='top' style='font-size: 12px;padding: 5px; background: white; border-top: 1px solid gray; height:100px; text-align: justify;' colspan='2'>$body</td>
		</tr>
	</table>";
}
