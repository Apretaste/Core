<?php


//
// TODO Controller ment for testing new code before adding it to the application
// TODO Remove this file when the system is going to production
//


use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$serviceName = "wikipedia"; // dynamic
		$templateName = "basic.tpl"; // dynamic

		// get the path
		$wwwroot = $this->di->get('path')['root'];

		// creating a new Smarty object
		$smarty = new Smarty;

		// changing locations
		$smarty->setTemplateDir("$wwwroot/app/layouts/");
		$smarty->setCompileDir("$wwwroot/temp/templates_c/");
		$smarty->setCacheDir("$wwwroot/temp/cache/");

		// disabling cache and debugging
		$smarty->force_compile = true;
		$smarty->debugging = false;
		$smarty->caching = false;

		// assign variables
		$smarty->assign("_USER_TEMPLATE", "$wwwroot/services/$serviceName/templates/$templateName");
		$smarty->assign("_SERVICE_NAME", "Apretaste");
		$smarty->assign("_SERVICE_RELATED", array("John", "Mary", "James", "Henry"));
		$smarty->assign("Name", "Fred Irving Johnathan Bradley Peppergill", true);
		$smarty->assign("FirstName", array("John", "Mary", "James", "Henry"));
		$smarty->assign("LastName", array("Doe", "Smith", "Johnson", "Case"));

		$templateVariables = array(
			"hr" => '<hr style="border:1px solid #D0D0D0; margin:0px;"/>',
			"separatorLinks" => '<span class="separador-links" style="color: #A03E3B;">&nbsp;|&nbsp;</span>',
			"space10" => '<div class="space_10">&nbsp;</div>',
			"space15" => '<div class="space_15" style="margin-bottom: 15px;">&nbsp;</div>',
			"space30" => '<div class="space_30" style="margin-bottom: 30px;">&nbsp;</div>',
		);
		$smarty->assign($templateVariables);
		
		// renderig
		$renderedTemplate = preg_replace('/\s+/S', " ", $smarty->fetch("email_default.tpl"));

		die(trim($renderedTemplate));
	}
}
