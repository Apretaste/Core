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
		// redirect default to true if not passed
		if(json.redirect == undefined) json.redirect = true;

		// make a simple redirect
		if(json.redirect) {
			var href = '/run/web?cm='+json.command;
			if(json.data) href += '&dt='+JSON.stringify(json.data);
			setTimeout(function() { // delay redirect to avoid errors
				window.location.replace(href);
			}, 50);
		}
		else{ 
			//send the data via post and stay in the same page
			$.post('/run/web?cm='+json.command, {'dt': JSON.stringify(json.data)});

			//call the callback
			if(json.callback) window[json.callback.name](json.callback.data);
		}

		return false;
	}
}
