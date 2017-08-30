<?php
/* Smarty version 3.1.29, created on 2017-08-10 22:44:26
  from "C:\workspace\Core\services\ayuda\templates\basic.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_598d1a0a0b83b0_06135171',
  'file_dependency' => 
  array (
    'a8e4efaaef90cd4ed64a57631af4647903f125fc' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\ayuda\\templates\\basic.tpl',
      1 => 1461527114,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_598d1a0a0b83b0_06135171 ($_smarty_tpl) {
if (!is_callable('smarty_function_space15')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space15.php';
if (!is_callable('smarty_function_apretaste_email')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.apretaste_email.php';
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
if (!is_callable('smarty_function_emailbox')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.emailbox.php';
if (!is_callable('smarty_function_space30')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space30.php';
if (!is_callable('smarty_function_apretaste_support_email')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.apretaste_support_email.php';
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
?>
<h1>Ayuda de Apretaste</h1>
<p>Apretaste le permite acceder a Internet mediante su email. Con Apretaste usted puede Comprar o Vender, consultar Wikipedia, Traducir documentos a decenas de idiomas, ver el Estado del Tiempo y mucho m&aacute;s; siempre desde su email.</p>

<?php echo smarty_function_space15(array(),$_smarty_tpl);?>


<table>
	<tr>
		<td valign="top">
			<h2>Navegue en internet por email</h2>
			<p><b>1.</b> Cree nuevo email. En la secci&oacute;n "Para" escriba: <?php echo smarty_function_apretaste_email(array(),$_smarty_tpl);?>
</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">NAVEGAR</span></p>
			<p><b>3.</b> Env&iacute;e el email. En segundos recibir&aacute; otro email con la p&aacute;gina de inicio del servicio NAVEGAR.</p>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

			<center>
				<?php echo smarty_function_button(array('href'=>"NAVEGAR revolico.com",'caption'=>"Probar NAVEGAR"),$_smarty_tpl);?>
 <?php echo smarty_function_button(array('href'=>"NAVEGAR",'caption'=>"Ir a NAVEGAR",'color'=>"blue"),$_smarty_tpl);?>


			</center>
		</td>
		<td valign="top">
			<?php echo smarty_function_emailbox(array('title'=>"Navegar",'from'=>((string)$_smarty_tpl->tpl_vars['userEmail']->value),'subject'=>"NAVEGAR revolico.com"),$_smarty_tpl);?>

		</td>
	</tr>
</table>


<?php echo smarty_function_space30(array(),$_smarty_tpl);?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>



<table>
	<tr>
		<td valign="top">
			<h2>Busque en la web con Google</h2>
			<p><b>1.</b> Cree un nuevo email. En la secci&oacute;n "Para" escriba: <?php echo smarty_function_apretaste_email(array(),$_smarty_tpl);?>
</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">GOOGLE</span> seguida de una frase referente a lo que desea buscar</p>
			<p><b>3.</b> Env&iacute;e el email. En segundos recibir&aacute; otro email con los mejores resultados de b&uacute;squeda en la web</p>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

			<center>
				<?php echo smarty_function_button(array('href'=>"GOOGLE pitufos",'caption'=>"Probar Google"),$_smarty_tpl);?>

			</center>
		</td>
		<td valign="top">
			<?php echo smarty_function_emailbox(array('title'=>"Google",'from'=>((string)$_smarty_tpl->tpl_vars['userEmail']->value),'subject'=>"GOOGLE pitufos"),$_smarty_tpl);?>

		</td>
	</tr>
</table>


<?php echo smarty_function_space30(array(),$_smarty_tpl);?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>



<table>
	<tr>
		<td valign="top">
			<h2>Con&eacute;ctate con miles de Cubanos y deja un trazo de tiza en una gran PIZARRA global</h2>
			<p><b>1.</b> Cree un nuevo email. En la secci&oacute;n "Para" escriba: <?php echo smarty_function_apretaste_email(array(),$_smarty_tpl);?>
</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">PIZARRA</span></p>
			<p><b>4.</b> Env&iacute;e el email. En segundos recibir&aacute; otro email con las &uacute;ltimos cien notas escritas en la pizarra.</p>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

			<center>
				<?php echo smarty_function_button(array('href'=>"PIZARRA",'caption'=>"Probar Traducir",'body'=>''),$_smarty_tpl);?>

			</center>
		</td>
		<td valign="top">
			<?php echo smarty_function_emailbox(array('title'=>"Pizarra",'from'=>((string)$_smarty_tpl->tpl_vars['userEmail']->value),'subject'=>"PIZARRA",'body'=>''),$_smarty_tpl);?>

		</td>
	</tr>
</table>


<?php echo smarty_function_space30(array(),$_smarty_tpl);?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>



<table>
	<tr>
		<td valign="top">
			<h2>Busca tu media naranja con CUPIDO</h2>
			<p><b>1.</b> Cree un nuevo email. En la secci&oacute;n "Para" escriba: <?php echo smarty_function_apretaste_email(array(),$_smarty_tpl);?>
</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">CUPIDO</span></p>
			<p><b>3.</b> Env&iacute;e el email. En segundos recibir&aacute; una lista de las personas m&aacute;s afines a usted</p>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

			<center>
				<?php echo smarty_function_button(array('href'=>"CUPIDO",'caption'=>"Probar Cupido"),$_smarty_tpl);?>

			</center>
		</td>
		<td valign="top">
			<?php echo smarty_function_emailbox(array('title'=>"Cupido",'from'=>((string)$_smarty_tpl->tpl_vars['userEmail']->value),'subject'=>"CUPIDO"),$_smarty_tpl);?>

		</td>
	</tr>
</table>


<?php echo smarty_function_space30(array(),$_smarty_tpl);?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>



<h1>Tenemos muchos m&aacute;s servicios</h1>
<p>Brindamos muchos m&aacute;s servicios, y todos los meses incrementamos la lista. &#191;Quiere sugerir alg&uacute;n servicio? &#191;Tiene alguna pregunta? Escribanos a <a href="mailto:<?php echo smarty_function_apretaste_support_email(array(),$_smarty_tpl);?>
"><?php echo smarty_function_apretaste_support_email(array(),$_smarty_tpl);?>
</a> y le atenderemos al momento.</p>
<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

<center>
	<?php echo smarty_function_button(array('href'=>"SERVICIOS",'caption'=>"Otros servicios"),$_smarty_tpl);?>

</center>

<?php echo smarty_function_space30(array(),$_smarty_tpl);?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>


<h1>Invite a sus amigos y familia</h1>
<p>&#191;Le gusta el trabajo que hacemos? Invite a sus amigos y familia a conocer Apretaste y gane tickets para <?php echo smarty_function_link(array('href'=>"RIFA",'caption'=>"nuestra rifa mensual"),$_smarty_tpl);?>
.</p>
<table>
	<tr>
		<td valign="top">
			<p><b>1.</b> Cree un nuevo email. En la secci&oacute;n "Para" escriba: <?php echo smarty_function_apretaste_email(array(),$_smarty_tpl);?>
</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">INVITAR</span> seguido del email de su amigo</p>
			<p><b>3.</b> Env&iacute;e el email. Su amigo ser&aacute; invitado y usted recibir&aacute; tickets para <?php echo smarty_function_link(array('href'=>"RIFA",'caption'=>"nuestra rifa"),$_smarty_tpl);?>
</p>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

			<center>
				<?php echo smarty_function_button(array('href'=>"INVITAR su@amigo.cu",'caption'=>"Invitar un amigo"),$_smarty_tpl);?>

			</center>
		</td>
		<td valign="top">
			<?php echo smarty_function_emailbox(array('title'=>"Invitar a un amigo",'from'=>((string)$_smarty_tpl->tpl_vars['userEmail']->value),'subject'=>"INVITAR su@amigo.cu"),$_smarty_tpl);?>

		</td>
	</tr>
</table>
<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

<?php }
}
