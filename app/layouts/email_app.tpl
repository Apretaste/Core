{if $APRETASTE_ENVIRONMENT == "app" && $APP_VERSION < $APP_LATEST_VERSION}
<table width="100%" cellspacing="0">
	<tr>
		<td align="center" bgcolor="#F6CED8">
			<p><small>Usted tiene una version antigua de la app; descargue la version {$APP_LATEST_VERSION} y saque maximo provecho a Apretaste</small></p>
		</td>
		<td align="right" bgcolor="#F6CED8" width="10">
			{button href="APP" caption="Descargar" size="small"}
		</td>
	</tr>
</table>
{/if}

{include file="$APRETASTE_USER_TEMPLATE"}
