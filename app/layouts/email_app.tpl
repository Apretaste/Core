{if $APRETASTE_ENVIRONMENT == "app" && $APP_VERSION < $APP_LATEST_VERSION}
<table width="100%" cellspacing="0" cellpadding="3">
	<tr>
		<td align="center" bgcolor="#F6CED8">
			<p><small>Descargue la version {$APP_LATEST_VERSION} de la app y pongase al dia</small></p>
		</td>
		<td align="right" bgcolor="#F6CED8" width="10">
			{button href="APP" caption="Descargar" size="small"}
		</td>
	</tr>
</table>
{/if}

{include file="$APRETASTE_USER_TEMPLATE"}
