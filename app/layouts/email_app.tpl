{if $APP_VERSION <= 2}
<table width="100%" cellspacing="0" cellpadding="3">
	<tr>
		<td align="left" bgcolor="#FCF8E3">
			<p><small>El 1ro de Febrero descontinuaremos la version {$APP_VERSION} de la app y debera instalar la ultima version</small></p>
		</td>
	</tr>
</table>
{space5}
{/if}

{*
{if $APP_VERSION <= 2}
	<p><b>Hemos descontinuado la version {$APP_VERSION} de la app. Por favor instale la ultima version.</b></p>
	<p>Su version de la app no le permite ahorrar datos con nuestros ultimos mecanismos de cache y compresion, no protege su privacidad a la altura de versiones superiores y no le dejara disfrutar de nuestros servicios mas modernos.</p>
	<p>Sabemos que esto es un inconveniente grave y le queremos pedir disculpas. Este es un paso imprescindible para avanzar nuestro proyecto hacia un mejor futuro. Pedimos disculpas en especial si acaba de instalar la app y recibe este texto. No es nuestra intencion alienarlo, nos encantaria que migrara a la ultima version y conociera la mejor cara que Apretaste puede ofrecerle.</p>
	<p>Muchas gracias por usar Apretaste. Instale la version {$APP_LATEST_VERSION} para continuar.</p>
	<center>
		{button href="APP" caption="Actualizar"}
	</center>
{else}
*}
	{if $APP_VERSION < $APP_LATEST_VERSION}
	<table width="100%" cellspacing="0" cellpadding="3">
		<tr>
			<td align="left" bgcolor="#F6CED8">
				<p><small>App desactualizada; baje la version {$APP_LATEST_VERSION|string_format:"%.1f"}</small></p>
			</td>
			<td align="right" bgcolor="#F6CED8" width="10">
				{button href="APP" caption="Descargar" size="small"}
			</td>
		</tr>
	</table>
	{/if}

	{include file="$APRETASTE_USER_TEMPLATE"}
{*
{/if}
*}
