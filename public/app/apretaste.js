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
		var href = '/run/web?cm='+json.command;

		// make a simple redirect
		if(json.redirect) {
			if(json.data) href += '&dt='+JSON.stringify(json.data);
			setTimeout(function() { // delay redirect to avoid errors
				window.location.replace(href);
			}, 50);
		}
		else{ 
			if(json.files==undefined){
				//send the data via post and stay in the same page
				$.post(href, {'dt': JSON.stringify(json.data)});
			}
			else{
				let form_data = new FormData();
				let n = 0;
				json.files.forEach(file => {
					form_data.append('file'+n, file);
					n++;
				});

				if(json.data) form_data.append('dt', JSON.stringify(json.data));

				$.ajax({
					url: href,
					type:"POST",
					cache:false,
					processData:false,
					contentType: false,
					data: form_data
				});
			}
			//call the callback
			if(json.callback) window[json.callback.name](json.callback.data);
		}

		return false;
	}
}
