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
		// prepare to make a clean request
		json.command = json.command.trim().replace(' ', '_');
		if(json.data != undefined) json.data = btoa(JSON.stringify(json.data));
		if(json.redirect == undefined) json.redirect = true;

		// make a simple redirect
		if(json.redirect) {
			var href = '/run/web?cm='+json.command;
			if(json.data) href += '&dt='+json.data;
			setTimeout(function() { // delay redirect to avoid Phalcon errors
				window.location.replace(href);
			}, 50);
		} else {
			//send the data via post and stay in the same page
			setTimeout(function() { // delay redirect to avoid Phalcon errors
				$.ajax({
					type: "GET",
					url: '/run/web?cm='+json.command,
					data: {'dt': json.data},
					success: function() {
						// run the callback
						if(json.callback.name != undefined && json.callback.name != "") {
							var data = json.callback.data == undefined ? {} : json.callback.data;
							if(json.callback) window[json.callback.name](data);							
						}
					}
				});
			}, 50);
		}

		return false;
	}
}
