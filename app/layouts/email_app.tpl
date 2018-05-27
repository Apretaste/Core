{if $TOP_AD}
<table width="100%" cellspacing="0" cellpadding="3">
	<tr>
		<td width="10" bgcolor="#F6CED8">
			<p><b>{$TOP_AD["icon"]}</b></p>
		</td>
		<td align="left" bgcolor="#F6CED8">
			<p><small>{$TOP_AD["text"]}</small></p>
		</td>
		<td align="right" bgcolor="#F6CED8" width="10">
			{button href="{$TOP_AD['link']}" caption="Ver" size="small" color="red"}
		</td>
	</tr>
</table>
{space5}
{/if}

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
{space5}
{/if}

{include file="$APRETASTE_USER_TEMPLATE"}
