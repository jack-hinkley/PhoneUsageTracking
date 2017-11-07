jQuery(document).ready(function($) {
	//	NAVBAR ACTIVE PAGE LINK
	var url = (window.location.pathname).substr(1, (window.location.pathname).length);
	if(url == '')
		url = '/'
	$.each($('.nav-item'), function(key, val){
		if($(val).find('a').attr('href') == url)
			$(val).addClass('active');
		else
			$(val).removeClass('active');
	});

	//	FILE INPUT CUSTOM
	$('input[type="file"]').change(function(e){
		$(this).parent().find($('span')).text(e.target.files[0].name);
	});
});

function parse_import(str){
	var data = str.replace(/ /g, '_')
		.replace(/\(/g, '_')
		.replace(/\)/g, '')
		.toLowerCase();
	data = data
		.replace(/_______/g, '_')
		.replace(/______/g, '_')
		.replace(/_____/g, '_')
		.replace(/____/g, '_')
		.replace(/___/g, '_')
		.replace(/__/g, '_');
	console.log(data);
}