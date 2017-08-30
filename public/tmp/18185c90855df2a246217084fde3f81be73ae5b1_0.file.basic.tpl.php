<?php
/* Smarty version 3.1.29, created on 2017-08-12 00:38:01
  from "C:\workspace\Core\services\clima\templates\basic.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_598e8629906099_09252606',
  'file_dependency' => 
  array (
    '18185c90855df2a246217084fde3f81be73ae5b1' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\clima\\templates\\basic.tpl',
      1 => 1502512553,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_598e8629906099_09252606 ($_smarty_tpl) {
if (!is_callable('smarty_modifier_date_format')) require_once 'C:\\workspace\\Core\\vendor\\smarty\\smarty\\libs\\plugins\\modifier.date_format.php';
if (!is_callable('smarty_function_space15')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space15.php';
if (!is_callable('smarty_function_space5')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space5.php';
if (!is_callable('smarty_function_img')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.img.php';
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
if (!is_callable('smarty_function_space30')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space30.php';
if (!is_callable('smarty_function_link')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.link.php';
?>
<h1>El Clima</h1>
<p><small><?php echo smarty_modifier_date_format(time(),"%A, %B %e, %Y");?>
</small></p>

<?php echo smarty_function_space15(array(),$_smarty_tpl);?>


<?php
$_from = $_smarty_tpl->tpl_vars['weather']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_w_0_saved_item = isset($_smarty_tpl->tpl_vars['w']) ? $_smarty_tpl->tpl_vars['w'] : false;
$__foreach_w_0_total = $_smarty_tpl->smarty->ext->_foreach->count($_from);
$_smarty_tpl->tpl_vars['w'] = new Smarty_Variable();
$__foreach_w_0_iteration=0;
$_smarty_tpl->tpl_vars['w']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['w']->value) {
$_smarty_tpl->tpl_vars['w']->_loop = true;
$__foreach_w_0_iteration++;
$_smarty_tpl->tpl_vars['w']->last = $__foreach_w_0_iteration == $__foreach_w_0_total;
$__foreach_w_0_saved_local_item = $_smarty_tpl->tpl_vars['w'];
?>
	<h2><?php echo $_smarty_tpl->tpl_vars['w']->value->location;?>
</h2>
	<table border="0" width="100%" cellpadding="10" cellspacing="0">
		<tr>
			<td align="center" valign="top" width="100" bgcolor="#F2F2F2">
				<b>Hoy</b>
				<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

				<?php echo smarty_function_img(array('src'=>((string)$_smarty_tpl->tpl_vars['w']->value->icon),'width'=>"60"),$_smarty_tpl);?>

				<?php echo smarty_function_space5(array(),$_smarty_tpl);?>

				<small><?php echo $_smarty_tpl->tpl_vars['w']->value->description;?>
</small> 
			</td>
			<td bgcolor="#F2F2F2">
				Temperatura: <?php echo $_smarty_tpl->tpl_vars['w']->value->temperature;?>
<br/>
				Viento: Hacia el <?php echo $_smarty_tpl->tpl_vars['w']->value->windDirection;?>
, a <?php echo $_smarty_tpl->tpl_vars['w']->value->windSpeed;?>
<br/>
				Precipitaciones: <?php echo $_smarty_tpl->tpl_vars['w']->value->precipitation;?>
<br/>
				Humedad: <?php echo $_smarty_tpl->tpl_vars['w']->value->humidity;?>
<br/>
				Visibilidad: <?php echo $_smarty_tpl->tpl_vars['w']->value->visibility;?>
<br/>
				Presi&oacute;n: <?php echo $_smarty_tpl->tpl_vars['w']->value->pressure;?>
<br/>
				Nubosidad: <?php echo $_smarty_tpl->tpl_vars['w']->value->cloudcover;?>
<br/>
				Actualizado: <?php echo $_smarty_tpl->tpl_vars['w']->value->time;?>

			</td>
		</tr>
	</table>
 
	<?php if (!$_smarty_tpl->tpl_vars['w']->last) {?>
		<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

		<hr/>
		<?php echo smarty_function_space10(array(),$_smarty_tpl);?>

	<?php }
$_smarty_tpl->tpl_vars['w'] = $__foreach_w_0_saved_local_item;
}
if ($__foreach_w_0_saved_item) {
$_smarty_tpl->tpl_vars['w'] = $__foreach_w_0_saved_item;
}
?>

<?php echo smarty_function_space30(array(),$_smarty_tpl);?>


<h1>Otras m&eacute;tricas</h1>
<ul>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA satelite",'caption'=>"Imagen del sat&eacute;lite"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA nasa",'caption'=>"Imagen de la NASA"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA caribe",'caption'=>"El Caribe</a>"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA radar",'caption'=>"Radar</a>"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA sector",'caption'=>"Sector visible"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA infrarroja",'caption'=>"Infrarroja"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA vapor",'caption'=>"Vapor de Agua"),$_smarty_tpl);?>
 </li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA temperatura",'caption'=>"Temperatura del mar"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA superficie",'caption'=>"Superficie del Atl&aacute;ntico y el Caribe"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA atlantico",'caption'=>"Estado del Atl&aacute;ntico"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA polvo",'caption'=>"Polvo del desierto"),$_smarty_tpl);?>
</li>
	<li><?php echo smarty_function_link(array('href'=>"CLIMA presion superficial",'caption'=>"Presi&oacute;n superficial"),$_smarty_tpl);?>
</li> 
</ul>
<?php }
}
