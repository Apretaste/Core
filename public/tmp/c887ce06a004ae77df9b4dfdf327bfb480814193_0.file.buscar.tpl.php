<?php
/* Smarty version 3.1.29, created on 2017-08-01 00:38:48
  from "C:\workspace\Core\services\tienda\templates\buscar.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_598005d8d737a0_78678797',
  'file_dependency' => 
  array (
    'c887ce06a004ae77df9b4dfdf327bfb480814193' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\tienda\\templates\\buscar.tpl',
      1 => 1501479743,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_598005d8d737a0_78678797 ($_smarty_tpl) {
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
if (!is_callable('smarty_function_img')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.img.php';
if (!is_callable('smarty_function_noimage')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.noimage.php';
if (!is_callable('smarty_function_separator')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.separator.php';
if (!is_callable('smarty_modifier_capitalize')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.truncate.php';
if (!is_callable('smarty_modifier_cuba_phone_format')) require_once 'C:\\workspace\\Core\\app\\plugins\\modifier.cuba_phone_format.php';
if (!is_callable('smarty_modifier_date_format')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.date_format.php';
if (!is_callable('smarty_function_hr')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.hr.php';
?>
<table width="100%">
	<tr>
		<td align="right"><small>
			<font color="gray">
				<?php echo $_smarty_tpl->tpl_vars['numberOfDisplayedResults']->value;?>
 de <?php echo $_smarty_tpl->tpl_vars['numberOfTotalResults']->value;?>
 anuncios encontrados.
				<?php if ($_smarty_tpl->tpl_vars['numberOfTotalResults']->value > 10) {?>
					<?php echo smarty_function_link(array('href'=>"TIENDA BUSCARTODO ".((string)$_smarty_tpl->tpl_vars['searchQuery']->value),'caption'=>"Ver m&aacute;s"),$_smarty_tpl);?>

				<?php }?>
			</font></small>
			<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

		</td>
		<td align="right"><?php echo smarty_function_button(array('caption'=>"Comprar en Apretaste",'size'=>"small",'href'=>"MERCADO"),$_smarty_tpl);?>
</td>
	</tr>
</table>

<?php
$_from = $_smarty_tpl->tpl_vars['items']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_tienda_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_foreach_tienda']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_tienda'] : false;
$__foreach_tienda_0_saved_item = isset($_smarty_tpl->tpl_vars['item']) ? $_smarty_tpl->tpl_vars['item'] : false;
$__foreach_tienda_0_total = $_smarty_tpl->smarty->ext->_foreach->count($_from);
$_smarty_tpl->tpl_vars['item'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['__smarty_foreach_tienda'] = new Smarty_Variable(array());
$__foreach_tienda_0_iteration=0;
$_smarty_tpl->tpl_vars['item']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->_loop = true;
$__foreach_tienda_0_iteration++;
$_smarty_tpl->tpl_vars['__smarty_foreach_tienda']->value['last'] = $__foreach_tienda_0_iteration == $__foreach_tienda_0_total;
$__foreach_tienda_0_saved_local_item = $_smarty_tpl->tpl_vars['item'];
?>
	<table width="100%">
		<tr>
			<td rowspan="3" align="left" width="110" valign="middle">
				<?php if ($_smarty_tpl->tpl_vars['item']->value->number_of_pictures > 0) {?>
					<?php ob_start();
echo md5($_smarty_tpl->tpl_vars['item']->value->source_url);
$_tmp1=ob_get_clean();
echo smarty_function_img(array('src'=>((string)$_smarty_tpl->tpl_vars['wwwroot']->value)."/public/tienda/".$_tmp1."_1.jpg",'alt'=>"Imagen del producto",'width'=>"100"),$_smarty_tpl);?>

				<?php } else { ?>
					<?php echo smarty_function_noimage(array(),$_smarty_tpl);?>

				<?php }?>
			</td>
			<td>
				<?php if ($_smarty_tpl->tpl_vars['item']->value->price != 0 && $_smarty_tpl->tpl_vars['item']->value->price != '') {?>
					<font color="#5EBB47">$<?php echo number_format($_smarty_tpl->tpl_vars['item']->value->price);?>
 <?php echo $_smarty_tpl->tpl_vars['item']->value->currency;?>
</font>
					<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php }?>

				<?php ob_start();
echo smarty_modifier_truncate(smarty_modifier_capitalize($_smarty_tpl->tpl_vars['item']->value->ad_title),45,' ...');
$_tmp2=ob_get_clean();
echo smarty_function_link(array('href'=>"TIENDA VER ".((string)$_smarty_tpl->tpl_vars['item']->value->id),'caption'=>$_tmp2),$_smarty_tpl);?>

			</td>
		</tr>
		<tr>
			<td valign="top">
				<?php if ($_smarty_tpl->tpl_vars['item']->value->ad_body != '') {?>
					<?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['item']->value->ad_body,175,' ...');?>

				<?php }?>
			</td>
		</tr>
		<tr>
			<td>
				<small><font color="gray">
				<?php if ($_smarty_tpl->tpl_vars['item']->value->number_of_pictures > 1) {?>
					<?php echo $_smarty_tpl->tpl_vars['item']->value->number_of_pictures;?>
 fotos
					<?php echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php }?>

				<?php if ($_smarty_tpl->tpl_vars['item']->value->contact_email_1 != '') {?>
					<?php echo $_smarty_tpl->tpl_vars['item']->value->contact_email_1;
echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php } elseif ($_smarty_tpl->tpl_vars['item']->value->contact_email_2 != '') {?>
					<?php echo $_smarty_tpl->tpl_vars['item']->value->contact_email_2;
echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php } elseif ($_smarty_tpl->tpl_vars['item']->value->contact_email_3 != '') {?>
					<?php echo $_smarty_tpl->tpl_vars['item']->value->contact_email_3;
echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php }?>

				<?php if ($_smarty_tpl->tpl_vars['item']->value->contact_cellphone != '') {?>
					<?php echo smarty_modifier_cuba_phone_format($_smarty_tpl->tpl_vars['item']->value->contact_cellphone);
echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php } elseif ($_smarty_tpl->tpl_vars['item']->value->contact_phone != '') {?>
					<?php echo smarty_modifier_cuba_phone_format($_smarty_tpl->tpl_vars['item']->value->contact_phone);
echo smarty_function_separator(array(),$_smarty_tpl);?>

				<?php }?>

				<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['item']->value->date_time_posted,"%d/%m/%Y");?>

				</font></small>
			</td>
		</tr>
	</table>

	<?php if (!(isset($_smarty_tpl->tpl_vars['__smarty_foreach_tienda']->value['last']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_tienda']->value['last'] : null)) {?>
		<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

		<?php echo smarty_function_hr(array(),$_smarty_tpl);?>

		<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

	<?php }
$_smarty_tpl->tpl_vars['item'] = $__foreach_tienda_0_saved_local_item;
}
if ($__foreach_tienda_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_foreach_tienda'] = $__foreach_tienda_0_saved;
}
if ($__foreach_tienda_0_saved_item) {
$_smarty_tpl->tpl_vars['item'] = $__foreach_tienda_0_saved_item;
}
}
}
