<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

function smarty_function_button($params, $template)
{
	// get params
	$href = $params["href"];
	$caption = $params["caption"];

	// get the body if exist
	if(isset($params["body"])) $body = $params["body"];
	else $body = "Envie+el+correo+tal+y+como+esta,+ya+esta+preparado+para+usted";

	// get a valid apretaste email address
	$utils = new Utils();
	$validEmailAddress = $utils->getValidEmailAddress();

	// create and return button
	return 
	"<!--[if mso]>
		<v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' href='mailto:$validEmailAddress?subject=$href&amp;body=$body' style='height:36px;v-text-anchor:middle;width:150px;' arcsize='5%' strokecolor='#5dbd00' fillcolor='#5EBB47'>
		<w:anchorlock/>
		<center style='color:#ffffff;font-family:Helvetica, Arial,sans-serif;font-size:16px;'>$caption</center>
		</v:roundrect>
	<![endif]-->
	<a href='mailto:$validEmailAddress?subject=$href&amp;body=$body' style='background-color:#5EBB47;border:1px solid #5dbd00;border-radius:3px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:16px;line-height:44px;text-align:center;text-decoration:none;width:150px;-webkit-text-size-adjust:none;mso-hide:all;'>$caption</a>";
}
