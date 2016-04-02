<table border="0" width="100%">
	<tr>
		<td><h1>Hace rato le extra&ntilde;amos en Apretaste</h1></td>
		<td width="40%">{img src="{$WWWROOT}/public/images/missyou.jpg" width="100%" alt="Se te extra&ntilde;a por Apretaste"}</td>
	</tr>
</table>

<p>Hace m&aacute;s de un mes que no usa Apretaste, y ya se le extra&ntilde;a. &iquest;Est&aacute; todo bien? Nuestro equipo est&aacute; trabajando duro para convertir Apretaste en la herramienta de su preferencia. Tal vez tenga dudas, o halla algo que no le guste, pero quiero que sepa que estamos aqu&iacute; para ayudarle. Escr&iacute;banos a <a href="mailto:soporte@apretaste.com">soporte@apretaste.com</a>, cu&eacute;ntenos que pasa y le atenderemos en persona.</p>

{if count($services) gt 0}
	{space10}
	<h2>Servicios que se est&aacute; perdiendo</h2>
	<p>&Uacute;ltimamente muchas cosas han cambiado. A continuaci&oacute;n una {link href="SERVICIOS" caption="lista de los servicios"} que se han agregado &oacute; mejorado mientras estuvo fuera:</p>
	
	<table border="0" width="100%">
		{foreach from=$services item=service}
		<tr>
			<td><b>{button href="{$service->name}" caption="{$service->name}"}</b></td>
			<td>&nbsp;</td>
			<td>{$service->description}</td>
		</tr>
		{/foreach}
	</table>
{/if}

{space5}

<p>Como regalo, le acabo de agregar <b>$1 de cr&eacute;dito</b> en su cuenta de Apretaste, para que compre tickets y participe en {link href="RIFA" caption="nuestra rifa mensual"}. Espero que nuestro gesto le sirva como incentivo para volver a nuestra querida familia.</p> 

<p>Y si Apretaste no le es &uacute;til ahora mismo, puede {link href="EXCLUYEME" caption="excluirse"} y no recibir&aacute; m&aacute;s nuestra correspondencia. Lo &uacute;ltimo que queremos es causarle alguna molestia.</p>