<?php
/* Smarty version 3.1.29, created on 2017-07-13 12:15:26
  from "C:\workspace\Core\services\pizarra\templates\pizarra.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_59679c9eac6a36_92772270',
  'file_dependency' => 
  array (
    '28b7b30820f57df8a41a8f5058e0f3008fa7f9ef' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\pizarra\\templates\\pizarra.tpl',
      1 => 1499754247,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_59679c9eac6a36_92772270 ($_smarty_tpl) {
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
if (!is_callable('smarty_function_space5')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space5.php';
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
if (!is_callable('smarty_function_separator')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.separator.php';
if (!is_callable('smarty_modifier_date_format')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.date_format.php';
if (!is_callable('smarty_modifier_replace_url')) require_once 'C:\\workspace\\Core\\app\\plugins\\modifier.replace_url.php';
if ($_smarty_tpl->tpl_vars['isProfileIncomplete']->value) {?>
<table width="100%">
	<tr>
		<td align="center" bgcolor="#F6CED8">
			<p><small>Para usar pizarra al m&aacute;ximo, <?php echo smarty_function_link(array('href'=>"PERFIL EDITAR",'caption'=>"complete su perfil"),$_smarty_tpl);?>
.</small></p>
		</td>
	</tr>
</table>
<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

<?php }?>

<table width="100%">
	<tr>
		<td>
			<h1>&Uacute;ltimas 50 notas</h1>
		</td>
		<td align="right" valign="top">
			<?php echo smarty_function_button(array('href'=>"PIZARRA reemplace este texto por su nota",'body'=>"Escriba una nota que no exeda los 130 caracteres en el asunto y envie este email",'caption'=>"&#10010; Escribir",'size'=>"small"),$_smarty_tpl);?>

			<?php echo smarty_function_button(array('href'=>"PIZARRA BUSCAR reemplace esto por un texto, @username o #hashtag a buscar",'body'=>'Escriba un texto a buscar, un @username o un #hashtag en el asunto, despues de la palabra BUSCAR, y envie este email. Por ejemplo: "PIZARRA BUSCAR amistad", "PIZARRA BUSCAR @apretaste" o "PIZARRA BUSCAR #cuba"','caption'=>"Buscar",'size'=>"small",'color'=>"grey"),$_smarty_tpl);?>

		</td>
	</tr>
</table>

<?php echo smarty_function_space5(array(),$_smarty_tpl);?>


<table width="100%">
<?php
$_from = $_smarty_tpl->tpl_vars['notes']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_note_0_saved_item = isset($_smarty_tpl->tpl_vars['note']) ? $_smarty_tpl->tpl_vars['note'] : false;
$_smarty_tpl->tpl_vars['note'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['note']->iteration=0;
$_smarty_tpl->tpl_vars['note']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['note']->value) {
$_smarty_tpl->tpl_vars['note']->_loop = true;
$_smarty_tpl->tpl_vars['note']->iteration++;
$__foreach_note_0_saved_local_item = $_smarty_tpl->tpl_vars['note'];
?>
	<tr <?php if (!(1 & $_smarty_tpl->tpl_vars['note']->iteration)) {?>bgcolor="#F2F2F2"<?php }?>>
		<td>
			<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

			<font color="gray">
				<small>
					<?php echo smarty_function_link(array('href'=>"PERFIL @".((string)$_smarty_tpl->tpl_vars['note']->value['username']),'caption'=>"@".((string)$_smarty_tpl->tpl_vars['note']->value['username'])),$_smarty_tpl);?>
,
					<?php echo $_smarty_tpl->tpl_vars['note']->value['location'];?>
,
					<?php if ($_smarty_tpl->tpl_vars['note']->value['gender'] == "M") {?><font color="#4863A0">M</font><?php }?>
					<?php if ($_smarty_tpl->tpl_vars['note']->value['gender'] == "F") {?><font color=#F778A1>F</font><?php }?>
					<?php if ($_smarty_tpl->tpl_vars['note']->value['picture']) {?>[foto]<?php }?>
					<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

					<font color="gray"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['note']->value['inserted'],"%e/%m %l:%M %p");?>
</font>
					<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

					<?php echo smarty_function_link(array('href'=>"PIZARRA BLOQUEAR @".((string)$_smarty_tpl->tpl_vars['note']->value['username']),'caption'=>"&#10006; Quitar",'body'=>"Envie este email para bloquear a @".((string)$_smarty_tpl->tpl_vars['note']->value['username'])." en tu Pizarra."),$_smarty_tpl);?>

				</small>
			</font>
			<br/>
			<big><big><?php echo smarty_modifier_replace_url($_smarty_tpl->tpl_vars['note']->value['text']);?>
</big></big>
			<br/>
			<small>
				<font color="green">+</font>&nbsp;<?php echo smarty_function_link(array('href'=>"PIZARRA LIKE ".((string)$_smarty_tpl->tpl_vars['note']->value['id']),'caption'=>"Bueno",'body'=>"Envie este email tal como esta para expresar gusto por este post de este usuario"),$_smarty_tpl);?>

				(<font><?php echo $_smarty_tpl->tpl_vars['note']->value['likes'];?>
</font>)
				<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

				<font color="red">-</font>&nbsp;<?php echo smarty_function_link(array('href'=>"PIZARRA UNLIKE ".((string)$_smarty_tpl->tpl_vars['note']->value['id']),'caption'=>"Malo",'body'=>"Envie este email tal como esta para expresar que este post no le gusta"),$_smarty_tpl);?>

				(<font><?php echo $_smarty_tpl->tpl_vars['note']->value['unlikes'];?>
</font>)
				<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php echo smarty_function_link(array('href'=>"PIZARRA ".((string)$_smarty_tpl->tpl_vars['note']->value['id'])."* Reemplace este texto por su comentario",'caption'=>"Comentar",'body'=>"Escriba en el asunto el comentario a la nota de @".((string)$_smarty_tpl->tpl_vars['note']->value['username'])." y envie este email."),$_smarty_tpl);?>

				<?php if ($_smarty_tpl->tpl_vars['note']->value['comments'] > 0) {?>
				<?php echo smarty_function_link(array('href'=>"PIZARRA NOTA ".((string)$_smarty_tpl->tpl_vars['note']->value['id']),'caption'=>"(".((string)$_smarty_tpl->tpl_vars['note']->value['comments']).")",'body'=>"Envie este email tal y como esta preparado para ver los comentarios de la nota."),$_smarty_tpl);?>

				<?php } else { ?>
				(0)
				<?php }?>				
			</small>
			<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

		</td>
	</tr>
<?php
$_smarty_tpl->tpl_vars['note'] = $__foreach_note_0_saved_local_item;
}
if ($__foreach_note_0_saved_item) {
$_smarty_tpl->tpl_vars['note'] = $__foreach_note_0_saved_item;
}
?>
</table>
<?php }
}
