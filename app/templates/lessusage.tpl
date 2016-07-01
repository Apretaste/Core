<h1>Lo que te est&aacute;s perdiendo en Apretaste</h1>

<p>Haz usado poco de los servicios que te brindamos. &iquest;No los conoces? Nuestro equipo est&aacute; 
trabajando duro para convertir Apretaste en la herramienta de su preferencia. Tal vez tengas dudas, o halla
algo que no te guste, pero queremos que sepas que estamos aqu&iacute; para ayudarte. 
Escr&iacute;benos a <a href="mailto:soporte@apretaste.com">soporte@apretaste.com</a>, cu&eacute;ntanos 
que pasa y te atenderemos en persona.</p>

{if count($services) gt 0}
	{space10}
	<h2>Servicios que se est&aacute; perdiendo</h2>
	<p>&Uacute;ltimamente muchas cosas han cambiado. A continuaci&oacute;n una {link href="SERVICIOS" caption="lista de los servicios"} que no has usado:</p>
	
	<table border="0" width="100%">
		{foreach from=$services item=service}
		<tr>
			<td><b>{button color="grey" href="{$service->name}" caption="{$service->name}"}</b></td>
			<td>&nbsp;</td>
			<td>{$service->description}</td>
		</tr>
		{/foreach}
	</table>
{/if}

{space5}

<p>Y si Apretaste no le es &uacute;til ahora mismo, puede {link href="EXCLUYEME" caption="excluirse"} y no recibir&aacute; m&aacute;s nuestra correspondencia. Lo &uacute;ltimo que queremos es causarle alguna molestia.</p>