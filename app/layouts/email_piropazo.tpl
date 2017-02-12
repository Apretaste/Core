<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Piropazo</title>
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
			body{
				font-family: Arial;
			}
			h1{
				color: #5DBB48;
				text-transform: uppercase;
				font-size: 22px;
				text-align: center;
				font-weight: normal;
			}
			h2{
				color: #5DBB48;
				text-transform: uppercase;
				font-size: 16px;
				margin-top: 30px;
				font-weight: normal;
			}
			.rounded{
				border-radius: 10px;
				background: white;
				padding: 10px;
			}
		</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="font-family: Arial;">
		<center>
			<table id="container" bgcolor="#F2F2F2" border="0" cellpadding="0" cellspacing="0" valign="top" align="center" width="600">
				<!--notifications-->
				{if $num_notifications > 0}
				<tr>
					<td align="right" valign="middle" style="padding:10px 30px;">
						{link href="NOTIFICACIONES" caption="&#9888;{$num_notifications}"}
					</td>
				</tr>
				{/if}

				<!--logo-->
				<tr>
					<td align="center" valign="middle">
						<div style="font-size:100px; color:#5DBB48;">&#x2764;</div>
					</td>
				</tr>

				<!--main section-->
				<tr>
					<td style="padding: 5px 10px 0px 10px;">
						<div class="rounded">
							{include file="$APRETASTE_USER_TEMPLATE"}
						</div>
					</td>
				</tr>

				<!--footer-->
				<tr>
					<td align="center" bgcolor="#F2F2F2" style="padding: 20px 0px;">
						<small>Piropazo @2017. All right reserved.</small>
					</td>
				</tr>
			</table>
		</center>
	</body>
</html>
