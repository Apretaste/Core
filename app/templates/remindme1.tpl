<table border="0" width="100%">
	<tr>
		<td><h1>Hace rato le extra&ntilde;amos en Apretaste</h1></td>
		<td width="40%">{img src="{$WWWROOT}/public/images/missyou.jpg" width="100%" alt="Se te extra&ntilde;a por Apretaste"}</td>
	</tr>
</table>

<p>Hace m&aacute;s de un mes que no usa Apretaste, y ya se le extra&ntilde;a. &iquest;Est&aacute; todo bien? Tal vez tenga dudas, o halla algo que no le guste, pero estamos aqu&iacute; para ayudarle. Escr&iacute;banos a <a href="mailto:soporte@apretaste.com">soporte@apretaste.com</a>, cu&eacute;ntenos que pasa y le atenderemos en persona.</p>

{if count($services) gt 0}
	{space10}
	<h2>Servicios que se est&aacute; perdiendo</h2>
	<p>&Uacute;ltimamente muchas cosas han cambiado. A continuaci&oacute;n una {link href="SERVICIOS" caption="lista de los servicios"} que se han agregado &oacute; mejorado mientras estuvo fuera:</p>
	
	<table border="1" width="100%">
		{foreach from=$services item=service}
		<tr>
			<td><b>{link href="{$service->name}" caption="{$service->name}"}</b></td>
			<td>{$service->description}</td>
		</tr>
		{/foreach}
	</table>
{/if}

{space10}

<p>Si Apretaste no le es &uacute;til ahora mismo, puede excluirse y no le llegar&aacute; m&aacute;s nuestra correspondencia.</p>
<center>{button href="EXCLUYEME" caption="EXCLUYEME"}</center>