<?php
/* Smarty version 3.1.29, created on 2017-08-10 22:45:26
  from "C:\workspace\Core\services\partitura\templates\basic.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_598d1a46b4a834_12102400',
  'file_dependency' => 
  array (
    '794ae57656c084f8768ecb5c2eeed10d6f5f38b6' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\partitura\\templates\\basic.tpl',
      1 => 1502332858,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_598d1a46b4a834_12102400 ($_smarty_tpl) {
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
?>
<h1>Resultados de acordes para '<?php echo $_smarty_tpl->tpl_vars['phrase']->value;?>
'</h1>

<?php if (count($_smarty_tpl->tpl_vars['artists']->value) > 0) {?>
<h2>Artistas encontrados</h2>
<ul>
<?php
$_from = $_smarty_tpl->tpl_vars['artists']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_artist_0_saved_item = isset($_smarty_tpl->tpl_vars['artist']) ? $_smarty_tpl->tpl_vars['artist'] : false;
$_smarty_tpl->tpl_vars['artist'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['artist']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['artist']->value) {
$_smarty_tpl->tpl_vars['artist']->_loop = true;
$__foreach_artist_0_saved_local_item = $_smarty_tpl->tpl_vars['artist'];
?>
	<li><?php echo $_smarty_tpl->tpl_vars['artist']->value['artist'];?>

	<ul>
		<?php
$_from = $_smarty_tpl->tpl_vars['songs']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_song_1_saved_item = isset($_smarty_tpl->tpl_vars['song']) ? $_smarty_tpl->tpl_vars['song'] : false;
$_smarty_tpl->tpl_vars['song'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['song']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['song']->value) {
$_smarty_tpl->tpl_vars['song']->_loop = true;
$__foreach_song_1_saved_local_item = $_smarty_tpl->tpl_vars['song'];
?>
			<li><?php echo $_smarty_tpl->tpl_vars['song']->value['title'];?>
</li>
		<?php
$_smarty_tpl->tpl_vars['song'] = $__foreach_song_1_saved_local_item;
}
if ($__foreach_song_1_saved_item) {
$_smarty_tpl->tpl_vars['song'] = $__foreach_song_1_saved_item;
}
?>
	</ul>
	</li>
<?php
$_smarty_tpl->tpl_vars['artist'] = $__foreach_artist_0_saved_local_item;
}
if ($__foreach_artist_0_saved_item) {
$_smarty_tpl->tpl_vars['artist'] = $__foreach_artist_0_saved_item;
}
?>
</ul>
<?php }?>

<?php
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['i']->step = 1;$_smarty_tpl->tpl_vars['i']->total = (int) ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? 9+1 - (0) : 0-(9)+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0) {
for ($_smarty_tpl->tpl_vars['i']->value = 0, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++) {
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration == 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration == $_smarty_tpl->tpl_vars['i']->total;?>
	<?php if (isset($_smarty_tpl->tpl_vars['titles']->value[$_smarty_tpl->tpl_vars['i']->value])) {?>
		<?php if (isset($_smarty_tpl->tpl_vars['newurls']->value[$_smarty_tpl->tpl_vars['i']->value][0])) {?>
			<tr>
				<td valign="top"><?php echo $_smarty_tpl->tpl_vars['titles']->value[$_smarty_tpl->tpl_vars['i']->value];?>
</td>
				<td width="20%" valign="top">
				<?php
$_from = $_smarty_tpl->tpl_vars['newurls']->value[$_smarty_tpl->tpl_vars['i']->value];
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_item_2_saved_item = isset($_smarty_tpl->tpl_vars['item']) ? $_smarty_tpl->tpl_vars['item'] : false;
$__foreach_item_2_saved_key = isset($_smarty_tpl->tpl_vars['key']) ? $_smarty_tpl->tpl_vars['key'] : false;
$_smarty_tpl->tpl_vars['item'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['key'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['item']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['key']->value => $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->_loop = true;
$__foreach_item_2_saved_local_item = $_smarty_tpl->tpl_vars['item'];
?>
					<?php echo smarty_function_link(array('href'=>"NAVEGAR ".((string)$_smarty_tpl->tpl_vars['item']->value),'caption'=>"Partitura #".((string)($_smarty_tpl->tpl_vars['key']->value+1))),$_smarty_tpl);?>
 <br/>
				<?php
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_2_saved_local_item;
}
if ($__foreach_item_2_saved_item) {
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_2_saved_item;
}
if ($__foreach_item_2_saved_key) {
$_smarty_tpl->tpl_vars['key'] = $__foreach_item_2_saved_key;
}
?>
				</td>
				<td width="20%" align="right" valign="top">
				<?php echo smarty_function_link(array('href'=>"LETRA ".((string)$_smarty_tpl->tpl_vars['Song']->value),'caption'=>"Ver Letra"),$_smarty_tpl);?>

				</td>
			</tr>
			<tr><td colspan="3"><hr/></td></tr>
		<?php }?>
	<?php }
}
}
?>

</table>

<h2>Canciones encontradas</h2>
<?php }
}
