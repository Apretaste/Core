<?php

class Input
{
	public $command; // servicio y subservicio: "PERFIL OJOS"
	public $data; // object con una combinacion {tag:value}
	public $files = []; // arreglo con el nombre de los archivos envieados
	public $ostype; // web/android/ios
	public $method; // email/http
	public $apptype; // web/original/single
	public $osversion; // float con la version del software del mobil usado
	public $appversion; // float con la version de la app que manda el request
	public $serviceversion; // integer con la version del servicio que se esta pidiendo
	public $token; // Nauta pass of the user. Yes/No when passed to the service
}
