<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\View\SSViewer;
use SilverStripe\Core\Extension;
/**
 * @package silverstripe-multisites
 */
class MultisitesSecurityExtension extends Extension{
	
	/**
	 * Sets the theme to the current site theme
	 **/
	function onBeforeSecurityLogin(){
		$site = Multisites::inst()->getCurrentSite();

		if($site && $site->Theme) {
			SSViewer::set_themes([$site->Theme, SSViewer::DEFAULT_THEME]);
		}
	}
}