(function($){
	$.entwine('ss', function($){

		// check if the html editor config "content_css" needs to be updated 
		// this will happen if the user has navigated to an area of the cms 
		// associated with a different site than the screen they came from
//		$(document).ajaxComplete(function(e, xhr, settings) {
//			var editorCSS = xhr.getResponseHeader('X-HTMLEditor_content_css');
//			if(editorCSS){
//				if(editorCSS != ssTinyMceConfig.content_css ){
//						ssTinyMceConfig.content_css = editorCSS;
//					$('textarea.htmleditor').redraw();
//				}
//			}
//		});

	});

	//override getTreeConfig from CMSMain.Tree.js to allow context menu on "HiddenClass" page types (namely Site)
	$.entwine('ss.tree', function($){
		$('body .cms-tree').entwine({
			getTreeConfig: function() {
				var self = this, config = this._super(), hints = this.getHints();
				config.plugins.push('contextmenu');
				config.contextmenu = {
					'items': function(node) {
						
						var menuitems = {
								'edit': {
									'label': ss.i18n._t('Tree.EditPage', 'Edit page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(
											self.data('urlEditpage'), obj.data('id')
										));
									}
								}
							};

						// Add "show as list"
						if(!node.hasClass('nochildren')) {
							menuitems['showaslist'] = {
								'label': ss.i18n._t('Tree.ShowAsList'),
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(
										self.data('urlListview') + '&ParentID=' + obj.data('id'),
										null,
										// Default to list view tab
										{tabState: {'pages-controller-cms-content': {'tabSelector': '.content-listview'}}}
									);
								}
							};
						}
						
						// Build a list for allowed children as submenu entries
						var pagetype = node.data('pagetype'),
							id = node.data('id'),
							disallowedChildren = ((typeof hints[pagetype] != 'undefined') && (typeof hints[pagetype].disallowedChildren != 'undefined')) ? hints[pagetype].disallowedChildren : ['Site'],
							allowedChildren = node.find('>a .item').data('allowedchildren'),
							disallowedClass,
							menuAllowedChildren = {},
							hasAllowedChildren = false;

						// Filter allowed
						if(disallowedChildren) {
							for(var i=0; i<disallowedChildren.length; i++) {
								disallowedClass = disallowedChildren[i];
								if(allowedChildren[disallowedClass]) {
									delete allowedChildren[disallowedClass];
							}
							}
						}

						// Convert to menu entries
						$.each(allowedChildren, function(klass, title){
							hasAllowedChildren = true;
							menuAllowedChildren["allowedchildren-" + klass ] = {
								'label': '<span class="jstree-pageicon"></span>' + title,
								'_class': 'class-' + klass,
								'action': function(obj) {
									$('.cms-container').entwine('.ss').loadPanel(
										$.path.addSearchParams(
											ss.i18n.sprintf(self.data('urlAddpage'), id, klass),
											self.data('extraParams')
										)
									);
								}
							};
						});
						
						if(hasAllowedChildren) {
							menuitems['addsubpage'] = {
									'label': ss.i18n._t('Tree.AddSubPage', 'Add page under this page', 100, 'Used in the context menu when right-clicking on a page node in the CMS tree'),
									'submenu': menuAllowedChildren
								};
						}				

						menuitems['duplicate'] = {
							'label': ss.i18n._t('Tree.Duplicate'),
							'submenu': [
								{
									'label': ss.i18n._t('Tree.ThisPageOnly'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												ss.i18n.sprintf(self.data('urlDuplicate'), obj.data('id')), 
												self.data('extraParams')
											)
										);
									}
								},{
									'label': ss.i18n._t('Tree.ThisPageAndSubpages'),
									'action': function(obj) {
										$('.cms-container').entwine('.ss').loadPanel(
											$.path.addSearchParams(
												ss.i18n.sprintf(self.data('urlDuplicatewithchildren'), obj.data('id')), 
												self.data('extraParams')
											)
										);
									}
								}
							]									
						};

						return menuitems;
					} 
				};
				return config;
			}
		});
	});
}(jQuery));
