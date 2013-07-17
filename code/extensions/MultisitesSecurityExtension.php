<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesSecurityExtension extends Extension{
	
	function onBeforeSecurityLogin(){
		$site = Multisites::inst()->getCurrentSite();

		if($site && $site->Theme) {
			SSViewer::set_theme($site->Theme);
		}
	}
}