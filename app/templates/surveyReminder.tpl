{assign var="s" value=""}
{if $missing > 1}{assign var="s" value="s"}{/if}

<p>Hace {$days} d&iacute;as comenz&oacute; la encuesta <b>{$title}</b>, pero nunca la termin&oacute;. Actualmente le falta {$missing} respuesta{$s} para ganar <b>${$value}</b>.</p>
<p>Esta encuesta termina el d&iacute;a {$deadline|date_format:"%d de %B del %Y"}, y una vez cerrada <u>no hay forma de responder y reclamar su cr&eacute;dito</u>. No queremos molestar; no le recordaremos m&aacute;s sobre esta encuesta, pero si ya comenz&oacute;, an&iacute;mese y term&iacute;nela ahora.</p>

{space5}

<center>
	{button href="ENCUESTA {$survey}" caption="Terminar Encuesta"}
</center>