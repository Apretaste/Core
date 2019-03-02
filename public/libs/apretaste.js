/**
 * Internal functions for the app
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
		if(json.data != undefined) json.data = btoa(encodeURIComponent(JSON.stringify(json.data)));
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
					url: href,
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

var setOnChangeHandler = true;

function loadFileToBase64(){
	$('input:file')[0].click();
	if(setOnChangeHandler){
		setOnChangeHandler = false;
		$('input:file').change(() => {
			let file = $('input:file').prop("files")[0];
			file.toBase64().then(data => {
				sendFile(data) // sendFile func must be defined in service script
			});
			return false;
		});
	}
}

File.prototype.toBase64 = function(){
	return new Promise(function(resolve, reject) {
		var reader = new FileReader();
		reader.onload = function(){resolve(reader.result.split(',')[1]);};
		reader.onerror = reject;
		reader.readAsDataURL(this);
	}.bind(this));
}
