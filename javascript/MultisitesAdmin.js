(function($){
	$.entwine('ss', function($){

		// check if the html editor config "content_css" needs to be updated 
		// this will happen if the user has navigated to an area of the cms 
		// associated with a different site than the screen they came from
		$(document).ajaxComplete(function(e, xhr, settings) {
			var editorCSS = xhr.getResponseHeader('X-HTMLEditor_content_css');
			if(editorCSS){
				if(editorCSS != ssTinyMceConfig.content_css ){
					ssTinyMceConfig.content_css = editorCSS;
					$('textarea.htmleditor').redraw();
				}
			}
		});

	});
}(jQuery));