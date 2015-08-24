<?php

use Phalcon\Mvc\Controller;

class TestController extends Controller
{
	public function indexAction()
	{
		$body = "# Su nombre, por ejemplo: NOMBRE = Juan Perez Gutierres
NOMBRE = Salvi Pascual

# Su Fecha de nacimiento, por ejemplo: CUMPLEANO = 23/08/1995
CUMPLEANOS = 1985-11-23

# Su Profesion resumida en una sola palabra, por ejemplo: Arquitecto
PROFESION = Programador

# Provincia donde vives
PROVINCIA = Miami-Dade

# Ciudad donde vives
CIUDAD = Miami-Dade

# Escoja entre: M o F, por ejemplo: SEXO = M
SEXO = M

# Escoja entre: primario, secundario, tecnico, universitario, postgraduado, doctorado u otro
NIVEL ESCOLAR = master

# Escoja entre: soltero,saliendo,comprometido o casado
ESTADO CIVIL = casado

# Escoja entre: trigueno, castano, rubio, negro, rojo, blanco u otro
PELO = trigueno

# Escoja entre: negro, blanco, mestizo u otro
PIEL = blanca

# Escoja entre: negro, carmelita, verde, azul, avellana u otro
OJOS = verdes

# Escoja entre delgado, medio, extra o atletico
CUERPO = medio

# Liste sus intereses separados por coma, ejemplo: INTERESES = carros, playa, musica
INTERESES = Networking, Amistad, Programacion, Apretaste


# Y no olvide adjuntar su foto!
";
		$rules = array(
			array("CUMPLEANOS", "date", null),
			array("PROVINCIA", "enum", array('PINAR_DEL_RIO','LA_HABANA','ARTEMISA','MAYABEQUE','MATANZAS','VILLA_CLARA','CIENFUEGOS','SANTI_SPIRITUS','CIEGO_DE_AVILA','CAMAGUEY','LAS_TUNAS','HOLGUIN','GRANMA','SANTIAGO_DE_CUBA','GUANTANAMO','ISLA_DE_LA_JUVENTUD')),
			array("SEXO", "gender", null),
			array("NIVEL ESCOLAR", "enum", array('PRIMARIO','SECUNDARIO','TECNICO','UNIVERSITARIO','POSTGRADUADO','DOCTORADO','OTRO')),
			array("ESTADO CIVIL", "enum", array('SOLTERO','SALIENDO','COMPROMETIDO','CASADO')),
			array("PELO", "enum", array('TRIGUENO','CASTANO','RUBIO','NEGRO','ROJO','BLANCO','OTRO')),
			array("PIEL", "enum", array('NEGRO','BLANCO','MESTIZO','OTRO')),
			array("OJOS", "enum", array('NEGRO','CARMELITA','VERDE','AZUL','AVELLANA','OTRO')),
			array("CUERPO", "enum", array('DELGADO','MEDIO','EXTRA','ATLETICO')),
			array("INTERESES", "list", null)
		);

		$surveyParser = new SurveyParser();
		$res = $surveyParser->parse($body, $rules);
		print_r($res);
		exit;
	}
}
