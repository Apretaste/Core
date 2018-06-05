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
			{button href="{$TOP_AD['link']}" caption="{$TOP_AD['caption']}" size="small" color="green"}
		</td>
	</tr>
</table>
{space5}
{/if}

{include file="$APRETASTE_USER_TEMPLATE"}
