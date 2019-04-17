showPokal = function(nr, maxNr) {
	let str = "";
	for(i = 0; i < nr; i++) {
		str+= String.fromCodePoint(0x1F3C6);
	}
	setTimeout(function() {
		window.location.hash = str;
		if(nr < maxNr) {
			showPokal(nr++, maxNr);
		}
	}, 500);
}

$( document ).ready(function() {
	try {
		showPokal(1, 13);
	}catch(e) {
		console.error("Failed: " + e);
	}
});