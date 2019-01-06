/**
 * Internal functions for the app
 * @author salvipascual
 */

var apretaste = {
	//
	// Envia info al Core. Puede redirect a otra pagina o llamar un callback
	// 
	// {
	// 	command: "PERFIL EDITAR", 
	// 	data: {"name":"Salvi", "eyes":"blue"}, 
	// 	redirect: false,
	// 	callback: {
	// 		name: "reloadReport",
	// 		data: {"id":<%= id %>}
	// 	}
	// }
	//
	send: function (json) {
		// call function
		console.log('send data to ' + json.command);

		// call callback
		if(json.callback) {
			window[json.callback](['hola']);
		}
	}
}
