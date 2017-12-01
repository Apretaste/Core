<?php

// including Google Analytics
// Only for the live website

if($_SERVER['HTTP_HOST'] == "apretaste.com") { ?>

<script async src="https://www.googletagmanager.com/gtag/js?id=UA-49715278-1"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', 'UA-49715278-1');
</script>

<?php } ?>
