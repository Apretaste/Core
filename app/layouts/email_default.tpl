<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$APRETASTE_SERVICE_NAME}</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<style type="text/css">
			@media only screen and (max-width: 600px) {
				#container {
					width: 100%;
				}
			}
			@media only screen and (max-width: 480px) {
				.button {
					display: block !important;
				}
				.button a {
					display: block !important;
					font-size: 18px !important; width 100% !important;
					max-width: 600px !important;
				}
				.section {
					width: 100%;
					margin: 2px 0px;
					display: block;
				}
				.phone-block {
					display: block;
				}
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="font-family: Arial;">
		<center>
			<table id="container" border="0" cellpadding="0" cellspacing="0" border="0" valign="top" align="center" width="600">
				<!--top links-->
					<tr>
					<td align="right" bgcolor="#D0D0D0" style="padding: 5px;">
						<small>
							{link href="AYUDA" caption="Ayuda"}{separator}
							{link href="INVITAR escriba aqui las direcciones email de sus amigos" caption="Invitar" body=""}{separator}
							{link href="ESTADO" caption="Mi estado"}{separator}
							{link href="SERVICIOS" caption="M&aacute;s servicios"}
						</small>
					</td>
				</tr>
	
				<!--logo & service name-->
				<tr>
					<td bgcolor="#F2F2F2" align="center" valign="middle">
						<table border="0">
							<tr>
								<td class="phone-block" style="margin-right: 20px;" valign="middle">
									<font size="10" face="Tahoma" color="#5ebb47"><i>A</i>pretaste</font>
									<font size="12" face="Curlz MT" color="#A03E3B">!</font>
								</td>
								<td class="phone-block" align="center" valign="middle">
									<font size="5" face="Tahoma" color="#A03E3B"><b>{$APRETASTE_SERVICE_NAME}</b></font>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<!--main section to load the user template-->
				<tr>
					<td align="left" style="padding: 0px 5px;">
						{space10}
						{include file="$APRETASTE_USER_TEMPLATE"}
					</td>
				</tr>

				<!--services related-->
				{if  $APRETASTE_SERVICE_RELATED|@count gt 0} 
				<tr>
					<td align="left" style="padding: 0px 5px;">
						{space10}
						<small>
							Servicios similares:
							{foreach $APRETASTE_SERVICE_RELATED as $APRETASTE_SERVICE}
								{link href="{$APRETASTE_SERVICE}" caption="{$APRETASTE_SERVICE}"}
								{if not $_SERVICE@last}{separator}{/if}
							{/foreach}
						</small>
					</td>
				</tr>
				{/if}

				<!--footer-->
				<tr>
					<td align="center">
						<hr style="border: 1px solid #A03E3B;" />
						<p>
							<small>
								Escriba dudas e ideas a <a href="mailto:{apretaste_support_email}">{apretaste_support_email}</a>. Lea nuestros {link href="TERMINOS" caption="T&eacute;rminos de uso"}.<br/> 
								Copyright &copy; 2012 - {'Y'|date} Pragres Corporation
							</small>
						</p>
					</td>
				</tr>
			</table>
		</center>
	</body>
</html>