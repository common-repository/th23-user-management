jQuery(document).ready(function($) {

	// Specify omsg actions, if not previously defined (eg by theme JS)
	var omsg = (typeof $.th23omsg !== 'undefined' && $.isFunction($.th23omsg)) ? $.th23omsg : function(object, action, context) {
		var box = object.closest('.th23-user-management-omsg');
		if(action == 'open') {
			box.fadeIn(500);			
		}
		else if(action == 'close_click') {
			box.fadeOut(200);
		}
		else if(action == 'close_auto') {
			box.fadeOut(1000);
		}
	};
	// Show message - once all external sources (ie CSS) have been loaded and applied
	$(window).load(function(){
		omsg($('.th23-user-management-omsg'), 'open', 'th23-user-management-omsg');
		// Trigger automatic fade-out - for success messages, depending on setting
		if(parseInt(tumJSlocal['omsg_timeout']) > 0) {
			setTimeout(function() { omsg($('.th23-user-management-omsg.success'), 'close_auto', 'th23-user-management-omsg'); }, tumJSlocal['omsg_timeout']);
		}
	});
	// Attach close by user click
	$('.th23-user-management-omsg .close').click(function() {
		omsg($(this), 'close_click', 'th23-user-management-omsg');
	});

});
