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
	send: function(json){
		// prepare to make a clean request
		json.command = json.command.trim().replace(' ', '_');
		if(json.redirect == undefined) json.redirect = true;
		if(json.callback == undefined) json.callback = false;
		if(json.data != undefined) json.data = btoa(JSON.stringify(json.data));
		var href = '/run/web?cm='+json.command;

		// make a simple redirect
		if(json.redirect){
			if(json.data) href += '&dt='+json.data;
			setTimeout(() => { // delay redirect to avoid errors
				window.location.replace(href);
			}, 50);
		}
		else{
			if(json.files==undefined){
				//send the data via post and stay in the same page
				setTimeout(() => { // delay redirect to avoid errors
					$.post(href, {'dt': json.data, 'rd': false});
				}, 50);
			}
			else{
				let form_data = new FormData();
				let n = 0;
				json.files.forEach(file => {
					form_data.append('file'+n, file);
					n++;
				});

				if(json.data) form_data.append('dt', json.data);
				form_data.append('rd', false);

				setTimeout(() => { // delay redirect to avoid errors
					$.ajax({
						url: href,
						type:"POST",
						cache:false,
						processData:false,
						contentType: false,
						data: form_data
					});
				}, 50);		
			}
			//call the callback
			if(json.callback) window[json.callback.name](json.callback.data);
		}
		return false;
	}
}
