<?php
/* Smarty version 3.1.29, created on 2017-07-13 12:15:25
  from "C:\workspace\Core\services\pizarra\layouts\pizarra.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_59679c9dcb4f02_94649794',
  'file_dependency' => 
  array (
    '6fb2b639be224e313785c42b2dfa3994e9daa639' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\pizarra\\layouts\\pizarra.tpl',
      1 => 1496980534,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_59679c9dcb4f02_94649794 ($_smarty_tpl) {
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<style type="text/css">
			@media only screen and (max-width: 600px) {
				#container {
					width: 100%;
				}
			}
			@media only screen and (max-width: 480px) {
				.button {
					display: block !important;
				}
				.button a {
					display: block !important;
					font-size: 18px !important; width: 100% !important;
					max-width: 600px !important;
				}
				.section {
					width: 100%;
					margin: 2px 0px;
					display: block;
				}
				.phone-block {
					display: block;
				}
			}
			body{
				font-family: Arial;
			}
			h1{
				color: #9E100A;
				text-transform: uppercase;
				font-size: 22px;
				font-weight: normal;
				margin-top: 0px;
			}
			.rounded{
				border-radius: 10px;
				background: white;
				padding: 10px;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="font-family: Arial;">
		<center>
			<table id="container" bgcolor="#F2F2F2" border="0" cellpadding="0" cellspacing="0" valign="top" align="center" width="600">
				<tr>
					<td width="50"></td>
					<!--logo-->
					<td align="center" valign="middle">
						<span style="color:#9E100A; font-size:60px; font-family:Times;"><i><b>P</b></i></span>
					</td>

					<!--notifications-->
					<td width="50" align="left" valign="top" style="padding-top:10px">
						<?php if ($_smarty_tpl->tpl_vars['num_notifications']->value > 0) {?>
							<?php echo smarty_function_link(array('href'=>"NOTIFICACIONES",'caption'=>"&#9888;".((string)$_smarty_tpl->tpl_vars['num_notifications']->value)),$_smarty_tpl);?>

						<?php }?>
					</td>
				</tr>

				<!--main section-->
				<tr>
					<td style="padding: 5px 10px 0px 10px;" colspan="3">
						<div class="rounded">
							<?php $_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['APRETASTE_USER_TEMPLATE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

						</div>
					</td>
				</tr>

				<!--footer-->
				<tr>
					<td align="center" colspan="3" bgcolor="#F2F2F2" style="padding: 20px 0px;">
						<small>&iquest;Tienes internet? Accede v&iacute;a <a target="_blank" href="http://pizarracuba.com">PizarraCuba.com</a></small>
					</td>
				</tr>
			</table>
		</center>
	</body>
</html>
<?php }
}
