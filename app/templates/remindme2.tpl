<h1>Hace rato no le veo</h1>

<p>Hace ya m&aacute;s de dos meses que no usa Apretaste, y estamos empezando a preguntarnos que ha pasado contigo. Si le hicimos algo, pedimos disculpas, pero por favor no se pierda, y si hay algo que le preocupa o no entienda, escr&iacute;banos a <a href="mailto:soporte@apretaste.com">soporte@apretaste.com</a> para poder ayudarle.</p>

<p>Le acabo de poner <b>$1 de cr&eacute;dito</b> en su cuenta de Apretaste, para que compre tickets y participe en {link href="RIFA" caption="nuestra rifa mensual"}. Espero que nuestro gesto le sirva como incentivo para volver a nuestra querida familia.</p> 

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

<p>Si Apretaste no le es &uacute;til ahora mismo, puede excluirse y no le molestaremos m&aacute;s. De hecho, <font color="#A94442">si en 30 d&iacute;as no usa Apretaste le excluiremos autom&aacute;ticamente</font>.</p>
<center>{button href="EXCLUYEME" caption="EXCLUYEME"}</center>