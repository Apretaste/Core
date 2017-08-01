<?php
/* Smarty version 3.1.29, created on 2017-07-29 00:29:17
  from "C:\workspace\Core\app\layouts\email_minimal.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_597c0f1d6ad604_33769613',
  'file_dependency' => 
  array (
    '567f61ad80d19912a65ea0413631f72b858d84e4' => 
    array (
      0 => 'C:\\workspace\\Core\\app\\layouts\\email_minimal.tpl',
      1 => 1481124311,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_597c0f1d6ad604_33769613 ($_smarty_tpl) {
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
echo smarty_function_space10(array(),$_smarty_tpl);?>

<?php $_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['APRETASTE_USER_TEMPLATE']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

<p align="center" style="color: gray;">Apretaste!</p><?php }
}
