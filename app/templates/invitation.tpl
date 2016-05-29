<h1>&iexcl;Bienvenido a Apretaste!</h1>

<p style="color:red;">Usted ha recibido este email porque <b><a href="mailto:{$author}">{$author}</a></b> le ha invitado a descubrir 
Apretaste.</p>

{space5}

<p>Apretaste le permite acceder a Internet mediante su email. Con Apretaste usted puede Vender o Comprar, consultar Wikipedia, Traducir documentos a decenas de idiomas, ver el Estado del Tiempo y m&aacute;s; siempre desde su email.</p>

{space15}

<table>
	<tr>
		<td valign="top">
			<h2>Navegue en internet por email</h2>
			<p><b>1.</b> Cree nuevo email. En la secci&oacute;n "Para" escriba: {apretaste_email}</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">NAVEGAR</span></p>
			<p><b>3.</b> Env&iacute;e el email. En segundos recibir&aacute; otro email con la p&aacute;gina de inicio del servicio NAVEGAR.</p>
			{space10}
			<center>
				{button href="NAVEGAR revolico.com" caption="Probar NAVEGAR"} {button href="NAVEGAR" caption="Ir a NAVEGAR" color="blue"}

			</center>
		</td>
		<td valign="top">
			{emailbox title="Navegar" from="{$userEmail}" subject="NAVEGAR revolico.com"}
		</td>
	</tr>
</table>

{space30}
{space10}


<h1>Tenemos muchos m&aacute;s servicios</h1>
<p>Somos m&aacute;s que una tienda. Tenemos muchos m&aacute;s servicios y todos los meses aumentamos la lista. Acceda a nuestra lista de servicios o consulte la ayuda para aprender m&aacute;s sobre Apretaste. Use los botones que se muestran debajo.</p>

{space10}

<table width="100%">
	<tr>
		<td align="center">{button href="AYUDA" caption="Ver la Ayuda"}</td>
		<td align="center">{button href="SERVICIOS" caption="M&aacute;s Servicios"}</td>
	</tr>
</table>


{space30}
{space10}


<h1>&iquest;Tienes preguntas?</h1>
<p>Cuando uno encuentra algo nuevo es normal tener dudas; por eso atendemos a nuestros usuarios personalmente. &iquest;Tienes preguntas? Escriba a nuestros especialistas a <a href="mailto:{apretaste_support_email}">{apretaste_support_email}</a> y le atenderemos con gusto.</p>
