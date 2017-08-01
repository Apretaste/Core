<?php
/* Smarty version 3.1.29, created on 2017-07-31 22:22:51
  from "C:\workspace\Core\services\tienda\templates\home.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_597fe5fbcf7b51_81199595',
  'file_dependency' => 
  array (
    '129efb4b6763830bdc5db40cd9bf68bf805b52a5' => 
    array (
      0 => 'C:\\workspace\\Core\\services\\tienda\\templates\\home.tpl',
      1 => 1501479743,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_597fe5fbcf7b51_81199595 ($_smarty_tpl) {
if (!is_callable('smarty_function_button')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.button.php';
if (!is_callable('smarty_function_space10')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.space10.php';
if (!is_callable('smarty_function_APRETASTE_EMAIL')) require_once 'C:\\workspace\\Core\\app\\plugins\\function.APRETASTE_EMAIL.php';
?>
<h1>Bienvenido a nuestra tienda</h1>

<p>Usando este servicio puede comprar art&iacute;culos o servicios que necesite, o poner algo a la venta.</p>

<p>Use el boton de abajo para buscar un art&iacute;culo o servicio a la venta</p>

<center>
	<?php echo smarty_function_button(array('caption'=>"Buscar",'href'=>"TIENDA",'body'=>"Escriba el articulo a buscar en el asunto despues de la palabra TIENDA, por ejemplo: TIENDA Televisor LCD",'desc'=>"Escriba el articulo o servicio a buscar. Por ejemplo: Televisor LCD",'popup'=>"true"),$_smarty_tpl);?>

</center>

<?php echo smarty_function_space10(array(),$_smarty_tpl);?>


<p>Si desea publicar un articulo o servicio ser vendido, cree un nuevo email y escriba en el asunto TIENDA PUBLICAR seguido del t&iacute;tulo del anuncio, y escriba en el cuerpo del mensaje mas informaci&oacute;n sobre el mismo. Escriba como parte del texto precio e informaci&oacute;n de contacto. Si es necesario, adjunte una imagen de su producto o servicio.</p>

<p>Por ejemplo:</p>

<p>
	<b>Para:</b> <?php echo smarty_function_APRETASTE_EMAIL(array(),$_smarty_tpl);?>
<br/>
	<b>Asunto:</b> TIENDA PUBLICAR vendo televisor lcd<br/>
	<b>Cuerpo:</b> Marca Sony, de 17 pulgadas, nuevo en su caja y listo para ser usando con garantia. Vale $350 CUC, y te lo dejo en tu casa. Llamame al 53654895 o escribeme a yo@nauta.cu
</p>

<p>Recibir&aacute; un email de confirmaci&oacute;n, y su anuncio ser&aacute; agregado a la tienda</p>
<?php }
}
