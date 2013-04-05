var maxwidth = 240;
var maxheight = 300;
function resize(img) {
    img.width = maxwidth;
    img.style.display = "inline-block";
/*
	// Apparently this is all overly elaborite and not need.  I'll keep it around just in case I found some issue with the absurdly simple solution above.
	if (navigator.appName == 'Microsoft Internet Explorer') {
		img.width = maxwidth;
		img.style.display = "inline-block";
	} else {
		var ratio = img.width / img.height;
		var w1 = maxwidth;
		var h1 = maxwidth / ratio;
		var w2 = maxheight * ratio;
		var h2 = maxheight;
		
		if (w2 > maxwidth) {
			img.width = w1;
			img.height = h1;
		} else {
			img.width = w2;
			img.height = h2;
		}
		img.style.display = "inline-block";
	}*/
	
}