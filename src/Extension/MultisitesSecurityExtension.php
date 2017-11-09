<?php

namespace Symbiote\Multisites\Extension;

use Symbiote\Multisites\Multisites;

use SilverStripe\View\SSViewer;
use SilverStripe\Core\Extension;

/**
 * Sets the site theme when someone tries to login on a particular URL
 * 
 * @package silverstripe-multisites
 */
class MultisitesSecurityExtension extends Extension
{

    /**
     * Sets the theme to the current site theme
     * */
    function onBeforeSecurityLogin()
    {
        $site = Multisites::inst()->getCurrentSite();

        if ($site && $site->Theme) {
            SSViewer::set_themes([$site->Theme, SSViewer::DEFAULT_THEME]);
        }
    }
}