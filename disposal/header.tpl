<TYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Conectamos al Cubano a servicios Utiles de Internet (estado del tiempo, traduccion, compra/venta, mapas, noticias y mucho mas) utilizando solamente el email, sin necesidad alguna de tener acceso a la Web.">
	<meta name="author" content="Salvi Pascual">
	<link rel="icon" href="<?php echo $wwwhttp; ?>/static/images/apretaste.icon.png">

	<title><?php echo isset($title) ? $title : "Apretaste!"; ?></title>

	<!-- Bootstrap core CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link href="<?php echo $wwwhttp; ?>/static/css/general.css" rel="stylesheet">

	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>

<body data-spy="scroll" data-target=".navbar">

	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<img width="150" style="margin:10px 0px 5px 0px;" src="<?php echo $wwwhttp; ?>/static/images/apretaste.logo.big.transp.png" alt="Apretaste!"/>
			<?php if(isset($title)){ ?><span style="color:gray; text-transform: uppercase;"><?php echo $title; ?></span><?php } ?>
		</div>
	</nav>
	<div class="container">

