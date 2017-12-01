<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
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
					font-size: 18px !important; width: 100% !important;
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
			h1{
				color: #5EBB47;
				text-decoration: underline;
				font-size: 24px;
				margin-top: 0px;
			}
			h2{
				color: #5EBB47;
				font-size: 16px;
				margin-top: 0px;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="font-family:Arial;">
		<center>
			<table id="container" border="0" cellpadding="0" cellspacing="0" valign="top" align="center" width="600" bgcolor="white">
				{* top links *}
				<tr>
					<td align="right" bgcolor="#D0D0D0" style="padding: 5px;">
						<small>
							{link href="SERVICIOS" caption="Servicios"}{separator}
							{link href="PERFIL" caption="&#9817; Perfil"}{separator}
							{link href="NOTIFICACIONES" caption="&#9888; Alertas ({$num_notifications})"}
							{if $APRETASTE_ENVIRONMENT eq "web"}
								&nbsp;<a style="background-color:#D9534F; text-decoration:none; padding:3px; color:white;" href="/logout">Logout</a>
							{/if}
						</small>
					</td>
				</tr>

				{* logo and service name *}
				<tr>
					<td bgcolor="#F2F2F2" align="center" valign="middle">
						<table border="0">
							<tr>
								<td class="phone-block" style="margin-right: 20px;" valign="middle">
									<span style="font-size:45px; white-space:nowrap; font-family:Tahoma; color:#5ebb47;">
										Apretaste<span style="color:#A03E3B;"><i>!</i></span>
									</span>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				{* main section to load the user template *}
				<tr>
					<td align="left" style="padding: 0px 5px;">
						{space10}
						{include file="$APRETASTE_USER_TEMPLATE"}
						{space10}
					</td>
				</tr>

				{* subscribe to email list *}
				{if ! $APRETASTE_EMAIL_LIST}
				<tr>
					<td align="center" bgcolor="red">
						<tr><td><table width="100%" cellpadding="0" cellspacing="0"><tr>
							<td bgcolor="#F0AD4E" valign="middle" width="1"><font color="red"><big>&nbsp;&#9888;&nbsp;</big></font></td>
							<td bgcolor="#F0AD4E"><small>Usted no esta suscrito a nuestra lista de correos, por lo cual no recibir&aacute; informaci&oacute;n sobre nuevos servicios, concursos y rifas.</small></td>
							<td bgcolor="#F0AD4E" align="right" valign="middle">
								{button href="SUSCRIPCION LISTA ENTRAR" caption="Suscribirse" size="small" color="green"}
							</td>
						</tr></table></td></tr>
					</td>
				</tr>
				{/if}

				{* footer *}
				<tr>
					<td align="center" bgcolor="#F2F2F2">
						{space5}
						<small>
							Apretaste &copy; {$smarty.now|date_format:"%Y"}; All rights reserved.<br/>
							{link href="SOPORTE" caption="Soporte"} {separator}
							{link href="TERMINOS" caption="T&eacute;rminos de uso"}
						</small>
						{space5}
					</td>
				</tr>
			</table>
		</center>
	</body>
</html>
