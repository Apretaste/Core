<?php
/* Smarty version 3.1.29, created on 2017-07-29 00:29:41
  from "C:\workspace\Core\services\nota\templates\chats.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_597c0f35dc7e84_11156433',
  'file_dependency' => 
  array (
    'a9e2b7f6d355434bcf6b6cb9868f99cdb7db7fe7' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\nota\\templates\\chats.tpl',
      1 => 1491122766,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_597c0f35dc7e84_11156433 ($_smarty_tpl) {
if (!is_callable('smarty_modifier_date_format')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.date_format.php';
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
if (!is_callable('smarty_function_space30')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space30.php';
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
?>
<h1>Charla con @<?php echo $_smarty_tpl->tpl_vars['friendUsername']->value;?>
</h1>

<table width="100%" cellspacing="0" cellpadding="5" border=0>
<?php
$_from = $_smarty_tpl->tpl_vars['chats']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_item_0_saved_item = isset($_smarty_tpl->tpl_vars['item']) ? $_smarty_tpl->tpl_vars['item'] : false;
$_smarty_tpl->tpl_vars['item'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['item']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->_loop = true;
$__foreach_item_0_saved_local_item = $_smarty_tpl->tpl_vars['item'];
?>
	<tr><td <?php if ($_smarty_tpl->tpl_vars['friendUsername']->value == $_smarty_tpl->tpl_vars['item']->value->username) {?>bgcolor="#F2F2F2"<?php }?>>
		<span style="color: #AAAAAA;"><small><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['item']->value->sent,"%e/%m/%Y %I:%M %p");?>
</small></span><br/>
		<b><?php echo smarty_function_link(array('href'=>"PERFIL @".((string)$_smarty_tpl->tpl_vars['item']->value->username),'caption'=>"@".((string)$_smarty_tpl->tpl_vars['item']->value->username)),$_smarty_tpl);?>
</b>:
		<span style="color:<?php if ($_smarty_tpl->tpl_vars['friendUsername']->value == $_smarty_tpl->tpl_vars['item']->value->username) {?>#000000<?php } else { ?>#000066<?php }?>;"><?php echo $_smarty_tpl->tpl_vars['item']->value->text;?>
</span>
	</td></tr>
<?php
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_0_saved_local_item;
}
if ($__foreach_item_0_saved_item) {
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_0_saved_item;
}
?>
</table>

<?php echo smarty_function_space30(array(),$_smarty_tpl);?>


<center>
	<?php echo smarty_function_button(array('href'=>"NOTA @".((string)$_smarty_tpl->tpl_vars['friendUsername']->value)." Reemplace este texto por su nota",'caption'=>"Responder",'body'=>'','color'=>"green",'size'=>"large"),$_smarty_tpl);?>

</center>
<?php }
}
