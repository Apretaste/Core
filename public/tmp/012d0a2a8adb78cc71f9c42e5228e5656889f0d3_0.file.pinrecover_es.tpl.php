<?php
/* Smarty version 3.1.29, created on 2017-07-29 00:29:17
  from "C:\workspace\Core\app\templates\pinrecover_es.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_597c0f1d73bf34_68634080',
  'file_dependency' => 
  array (
    '012d0a2a8adb78cc71f9c42e5228e5656889f0d3' => 
    array (
      0 => 'C:\\workspace\\Core\\app\\templates\\pinrecover_es.tpl',
      1 => 1495679334,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_597c0f1d73bf34_68634080 ($_smarty_tpl) {
?>
<center>
	<p>Su c&oacute;digo secreto es:</p>

	<div style="font-family:Tahoma; font-size:50px; margin-bottom:50px;">
		<?php echo $_smarty_tpl->tpl_vars['pin']->value;?>

	</div>

	<p>Use este c&oacute;digo para registrarse en nuestra app. Si usted no esperaba este c&oacute;digo, elimine este email ahora y no comparta el n&uacute;mero con nadie.</p>
</center>
<?php }
}
