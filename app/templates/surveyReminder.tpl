<h1>Termina las encuestas</h1>
<p align="justify">A continuaci&oacute;n te mostramos una lista de las encuestas que empezaste a 
responder pero a&uacute;n no has terminado. Quedan menos de 3 d&iacute;as para que estas 
encuestas cierren. Recuerda que <b>obtendr&aacute;s cr&eacute;dito termin&aacute;ndolas.</b></p>

<ul>
{foreach item=item from=$surveys}
<li>{button size="small" caption="${$item->value|number_format:2}" href="" color="green"} {link href="ENCUESTA {$item->id}" caption="{$item->title}"}<br/><br/></li>
{/foreach}
</ul>