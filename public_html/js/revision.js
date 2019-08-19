function generateReport() {
	var infoArray = {
		type: "DOWNLOAD"
	};
	$.ajax({
		type: "POST",
		data: infoArray,
		url: "/requests/revision.php",
		dataType: "json",
		success: function(json){
			downloadSuccess(json);
		},
		error: function(json){
			console.log("There was an error deleting the worksheet.");
		}
	});
}

function downloadSuccess(json) {
	if (json["success"]) {
		var link = document.createElement("a");
		link.setAttribute("href", json["url"]);
		link.setAttribute("download", "Maths Revision.pdf");
		document.body.appendChild(link);
		link.click();
	}
}