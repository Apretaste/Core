<p style="color:red;">El {$date|date_format:"%d de %B"}, {$inviter} le invit&oacute; a descubrir Apretaste y usted a&uacute;n no ha aprovechado esta invitaci&oacute;n.</p>

<p>Su amigo <u>{$inviter}</u> valora Apretaste y le invit&oacute; a que se beneficie usted tambi&eacute;n. No sea descortez y heche una mirada al trabajo que hacemos.</p>

{space5}

<p>Apretaste le permite acceder a internet mediante su email. Con Apretaste usted puede {link href="NAVEGAR" caption="revisar la web"}, {link href="TIENDA televisor lcd" caption="vender o comprar"}, consultar {link href="WIKIPEDIA jose marti" caption="wikipedia"}, buscar {link href="CUPIDO" caption="su pareja ideal"}, consultar el {link href="DOCTOR dolor de cabeza" caption="doctor" body="Escriba su sintoma en el asunto, despues de la palabra DOCTOR"} y {link href="SERVICIOS" caption="mucho m&aacute;s"}; siempre desde su email.</p>

<p>Por ejemplo ...</p>

{space15}

<h1>Busque amigos por Cuba y el mundo</h1>
<p>Int&eacute;grese a "Pizarra", nuestra Red Social global. Lea lo que otros escriben, comparta sus vivencias, y forje amistades sin precedentes.</p>
<table>
	<tr>
		<td valign="top">
			<p><b>1.</b> Cree un nuevo email. En la secci&oacute;n "Para" escriba: {apretaste_email}</p>
			<p><b>2.</b> En la secci&oacute;n "Asunto" escriba: <span style="color:green;">PIZARRA</span></p>
			<p><b>3.</b> Env&iacute;e el email. En segundos recibir&aacute; otro email con nuestra red social</p>
			{space10}
			<center>
				{button href="PIZARRA" caption="Usar Pizarra"}
			</center>
		</td>
		<td valign="top">
			{emailbox title="Nuevo email" from="{$invited}" subject="PIZARRA"}
		</td>
	</tr>
</table>


{space30}
{space10}


<h1>Tenemos muchos otros servicios</h1>
<p>Al igual que Pizarra, tenemos muchos otros servicios &uacute;tiles, y todos los meses agregamos m&aacute;s. Vea la lista de servicios y aprenda m&aacute;s sobre Apretaste.</p>

{space10}

<center>
	{button href="SERVICIOS" caption="Lista de Servicios"}
</center>


{space30}
{space10}


<h1>&iquest;Tienes preguntas?</h1>
<p>Ante lo nuevo es normal tener dudas; por eso atendemos a nuestros usuarios personalmente. &iquest;Tienes preguntas? Pregunte a nuestros especialistas escribiendo a <a href="mailto:{apretaste_support_email}">{apretaste_support_email}</a> y le atenderemos con gusto.</p>

{space30}

<p style="color:red;"><b>Importante:</b> Esta invitaci&oacute;n expira el <u>{$expires|date_format:"%d de %B del %Y"}</u>. Si no utiliza Apretaste en ese plazo, le eliminaremos del sistema y no recibir&aacute; m&aacute;s emails nuestros.</p>
