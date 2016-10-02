{setlocale type="all" locale="es_ES"}
<h1>&Uacute;ltimas 50 notificaciones</h1>
<table width="100%" cellpadding="5">
{foreach from=$notificactions item=notif}
	<tr  {if $notif@iteration is even}bgcolor="#F2F2F2"{/if}>
		<td {if $notif->viewed == '1'} 
			style="border-left:5px solid grey;" 
		{else} 
			style="border-left:5px solid green;" 
		{/if}>
		 <small>{$notif->inserted_date|date_format:"%B %e, %Y %l:%M %p"}</small><br/>
		 {$notif->text} 
		</td>
		<td width="30">
			{if $notif->link !== ''}
				{button caption="Ver" color="gree" size="small" href="{$notif->link}"}
			{/if}
		</td>
	</tr>
{/foreach}
</table>