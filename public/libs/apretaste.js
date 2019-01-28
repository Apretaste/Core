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
	send: function(json) {
		// prepare to make a clean request
		json.command = json.command.trim().replace(' ', '_');
		if(json.redirect == undefined) json.redirect = true;
		if(json.callback == undefined) json.callback = false;
		if(json.data != undefined) json.data = btoa(JSON.stringify(json.data));
		var href = '/run/web?cm='+json.command;

		// make a simple redirect
		if(json.redirect) {
			if(json.data) href += '&dt='+json.data;
			setTimeout(() => { // delay redirect to avoid errors
				window.location.replace(href);
			}, 100);
		} else {
			//send the data via post and stay in the same page
			setTimeout(function() { // delay redirect to avoid Phalcon errors
				$.ajax({
					type: "POST",
					url: '/run/web?cm='+json.command,
					data: {'dt': json.data},
					success: function() {
						// run the callback
						if(json.callback != undefined && json.callback.name != undefined && json.callback.name != "") {
							var data = json.callback.data == undefined ? {} : json.callback.data;
							if(json.callback) window[json.callback.name](data);							
						}
					}
				});
			}, 100);
		}

		return false;
	}
}
