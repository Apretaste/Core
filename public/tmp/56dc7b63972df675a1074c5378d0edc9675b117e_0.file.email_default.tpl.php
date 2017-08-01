<?php
/* Smarty version 3.1.29, created on 2017-08-01 00:38:47
  from "C:\workspace\Core\app\layouts\email_default.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_598005d77577c5_62728663',
  'file_dependency' => 
  array (
    '56dc7b63972df675a1074c5378d0edc9675b117e' => 
    array (
      0 => 'C:\\workspace\\Core\\app\\layouts\\email_default.tpl',
      1 => 1498489999,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_598005d77577c5_62728663 ($_smarty_tpl) {
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
if (!is_callable('smarty_function_separator')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.separator.php';
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
if (!is_callable('smarty_function_space5')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space5.php';
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
			h1{
				color: #5EBB47;
				text-decoration: underline;
				font-size: 24px;
				margin-top: 0px;
			}
			h2{
				color: #5EBB47;
				font-size: 16px;
				margin-top: 0px;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="font-family: Arial;">
		<center>
			<table id="container" border="0" cellpadding="0" cellspacing="0" valign="top" align="center" width="600">
				
				<tr>
					<td align="right" bgcolor="#D0D0D0" style="padding: 5px;">
						<small>
							<?php echo smarty_function_link(array('href'=>"AYUDA",'caption'=>"Ayuda"),$_smarty_tpl);
echo smarty_function_separator(array(),$_smarty_tpl);?>

							<?php echo smarty_function_link(array('href'=>"INVITAR escriba aqui las direcciones email de sus amigos",'caption'=>"Invitar",'body'=>''),$_smarty_tpl);
echo smarty_function_separator(array(),$_smarty_tpl);?>

							<?php echo smarty_function_link(array('href'=>"PERFIL",'caption'=>"Profile"),$_smarty_tpl);
echo smarty_function_separator(array(),$_smarty_tpl);?>

							<?php echo smarty_function_link(array('href'=>"SERVICIOS",'caption'=>"Servicios"),$_smarty_tpl);
echo smarty_function_separator(array(),$_smarty_tpl);?>

							<?php echo smarty_function_link(array('href'=>"NOTIFICACIONES",'caption'=>"Alertas"),$_smarty_tpl);?>

							<?php if ($_smarty_tpl->tpl_vars['num_notifications']->value > 0) {?>
								<!--[if mso]>
								<v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' style='v-text-anchor:middle;' arcsize='5%' strokecolor='$stroke' fillcolor='#FF0000'>
								<w:anchorlock/>
								<center style='color:#FFFFFF;font-family:Helvetica, Arial,sans-serif;font-size:9px;'><strong><?php echo $_smarty_tpl->tpl_vars['num_notifications']->value;?>
</strong></center>
								</v:roundrect>
								<![endif]-->
								<a style='background-color:#FF0000;border-radius:3px;color:#FFFFFF;display:inline-block;font-family:sans-serif;font-size:9px;text-align:center;text-decoration:none;line-height:20px;padding-left:2px;padding-right:2px;-webkit-text-size-adjust:none;mso-hide:all;'><b><?php echo $_smarty_tpl->tpl_vars['num_notifications']->value;?>
</b></a>
							<?php }?>
						</small>
					</td>
				</tr>

				
				<tr>
					<td bgcolor="#F2F2F2" align="center" valign="middle">
						<table border="0">
							<tr>
								<td class="phone-block" style="margin-right: 20px;" valign="middle">
									<span style="font-size:45px; white-space:nowrap; font-family:Tahoma; color:#5ebb47;">
										Apretaste<span style="color:#A03E3B;"><i>!</i></span>
									</span>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				
				<?php if (count($_smarty_tpl->tpl_vars['APRETASTE_ADS']->value) > 0) {?>
				<tr><td><table width="100%" cellpadding="0" cellspacing="0"><tr>
					<td bgcolor="#c3daee" valign="middle" width="1"><font color="#337AB7"><big><b>&#9733;</b>&nbsp;</big></font></td>
					<td bgcolor="#c3daee"><small><?php echo $_smarty_tpl->tpl_vars['APRETASTE_ADS']->value[0]->title;?>
</small></td>
					<td bgcolor="#c3daee" align="right" valign="middle">
						<?php echo smarty_function_button(array('href'=>"PUBLICIDAD ".((string)$_smarty_tpl->tpl_vars['APRETASTE_ADS']->value[0]->id),'caption'=>"Ver m&aacute;s",'size'=>"small",'color'=>"blue"),$_smarty_tpl);?>

					</td>
				</tr></table></td></tr>
				<?php }?>

				
				<?php if ($_smarty_tpl->tpl_vars['requests_today']->value == 0) {?>
					<?php if ($_smarty_tpl->tpl_vars['raffle_stars']->value > 0) {?>
					<tr>
						<td align="center">
							<big>
								<br/>
								<?php if ($_smarty_tpl->tpl_vars['raffle_stars']->value < 5) {?>
									Tu primer correo del d&iacute;a<br/>
								<?php } else { ?>
									<b>&iexcl;FELICIDADES!</b><br/>
								<?php }?>
								<?php
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['i']->step = 1;$_smarty_tpl->tpl_vars['i']->total = (int) ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? 5+1 - (1) : 1-(5)+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0) {
for ($_smarty_tpl->tpl_vars['i']->value = 1, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++) {
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration == 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration == $_smarty_tpl->tpl_vars['i']->total;?>
									<?php if ($_smarty_tpl->tpl_vars['i']->value <= $_smarty_tpl->tpl_vars['raffle_stars']->value) {?>
										<font color="black" size="5"><b>&#9733;</b></font>
									<?php } else { ?>
										<font color="black" size="5">&#9734;</font>
									<?php }?>
								<?php }
}
?>

								<br/>
							</big>
							<small>
								<?php if ($_smarty_tpl->tpl_vars['raffle_stars']->value < 5) {?>
									Por 5 d&iacute;as consecutivos ganar&aacute;s &sect;1 de cr&eacute;dito personal<br/>
									<?php if ($_smarty_tpl->tpl_vars['raffle_stars']->value > 0) {?><i>Ya vas por <?php echo $_smarty_tpl->tpl_vars['raffle_stars']->value;?>
. &iexcl;Emb&uacute;llate!</i><?php }?>
								<?php } else { ?>
									Haz ganado <b>&sect;1</b> de cr&eacute;dito personal<br/>
									Entra ma&ntilde;ana y gana incluso m&aacute;s cr&eacute;dito
								<?php }?>
							</small>
						</td>
					</tr>
					<?php }?>
				<?php }?>

				
				<tr>
					<td align="left" style="padding: 0px 5px;">
						<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

						<?php $_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['APRETASTE_USER_TEMPLATE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

						<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

					</td>
				</tr>

				
				<?php if (!$_smarty_tpl->tpl_vars['APRETASTE_EMAIL_LIST']->value) {?>
				<tr>
					<td align="center" bgcolor="red">
						<tr><td><table width="100%" cellpadding="0" cellspacing="0"><tr>
							<td bgcolor="#F0AD4E" valign="middle" width="1"><font color="red"><big>&nbsp;&#9888;&nbsp;</big></font></td>
							<td bgcolor="#F0AD4E"><small>Usted no esta suscrito a nuestra lista de correos, por lo cual no recibir&aacute; informaci&oacute;n sobre nuevos servicios, concursos y rifas.</small></td>
							<td bgcolor="#F0AD4E" align="right" valign="middle">
								<?php echo smarty_function_button(array('href'=>"SUSCRIPCION LISTA ENTRAR",'caption'=>"Suscribirse",'size'=>"small",'color'=>"green"),$_smarty_tpl);?>

							</td>
						</tr></table></td></tr>
					</td>
				</tr>
				<?php }?>

				
				<?php if (count($_smarty_tpl->tpl_vars['APRETASTE_ADS']->value) > 1) {?>
				<tr><td><table width="100%" cellpadding="0" cellspacing="0"><tr>
					<td bgcolor="#c3daee" valign="middle" width="1"><font color="#337AB7"><big><b>&#9733;</b>&nbsp;</big></font></td>
					<td bgcolor="#c3daee"><small><?php echo $_smarty_tpl->tpl_vars['APRETASTE_ADS']->value[1]->title;?>
</small></td>
					<td bgcolor="#c3daee" align="right" valign="middle">
						<?php echo smarty_function_button(array('href'=>"PUBLICIDAD ".((string)$_smarty_tpl->tpl_vars['APRETASTE_ADS']->value[1]->id),'caption'=>"Ver m&aacute;s",'size'=>"small",'color'=>"blue"),$_smarty_tpl);?>

					</td>
				</tr></table></td></tr>
				<?php }?>

				
				<?php if (count($_smarty_tpl->tpl_vars['APRETASTE_SERVICE_RELATED']->value) > 0) {?>
				<tr bgcolor="#e6e6e6">
					<td align="left" style="padding: 0px 5px;">
						<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

						<small>
							Otros servicios:
							<?php
$_from = $_smarty_tpl->tpl_vars['APRETASTE_SERVICE_RELATED']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_APRETASTE_SERVICE_0_saved_item = isset($_smarty_tpl->tpl_vars['APRETASTE_SERVICE']) ? $_smarty_tpl->tpl_vars['APRETASTE_SERVICE'] : false;
$__foreach_APRETASTE_SERVICE_0_total = $_smarty_tpl->smarty->ext->_foreach->count($_from);
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE'] = new Smarty_Variable();
$__foreach_APRETASTE_SERVICE_0_iteration=0;
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->value) {
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->_loop = true;
$__foreach_APRETASTE_SERVICE_0_iteration++;
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->last = $__foreach_APRETASTE_SERVICE_0_iteration == $__foreach_APRETASTE_SERVICE_0_total;
$__foreach_APRETASTE_SERVICE_0_saved_local_item = $_smarty_tpl->tpl_vars['APRETASTE_SERVICE'];
?>
								<?php echo smarty_function_link(array('href'=>((string)$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->value),'caption'=>((string)$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->value)),$_smarty_tpl);?>

								<?php if (!$_smarty_tpl->tpl_vars['APRETASTE_SERVICE']->last) {
echo smarty_function_separator(array(),$_smarty_tpl);
}?>
							<?php
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE'] = $__foreach_APRETASTE_SERVICE_0_saved_local_item;
}
if ($__foreach_APRETASTE_SERVICE_0_saved_item) {
$_smarty_tpl->tpl_vars['APRETASTE_SERVICE'] = $__foreach_APRETASTE_SERVICE_0_saved_item;
}
?>
						</small>
						<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

					</td>
				</tr>
				<?php }?>

				
				<tr>
					<td align="center" bgcolor="#F2F2F2">
						<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

						<small>
							&iquest;Tiene dudas? Escriba a <a href="mailto:<?php echo $_smarty_tpl->tpl_vars['APRETASTE_SUPPORT_EMAIL']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['APRETASTE_SUPPORT_EMAIL']->value;?>
</a><br/>
							Mire los <?php echo smarty_function_link(array('href'=>"TERMINOS",'caption'=>"T&eacute;rminos de uso"),$_smarty_tpl);?>
<br/>
						</small>
						<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

					</td>
				</tr>
			</table>
		</center>
	</body>
</html>
<?php }
}
