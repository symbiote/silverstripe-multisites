<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesSecurityExtension extends Extension
{
    
    /**
     * Sets the theme to the current site theme
     **/
    public function onBeforeSecurityLogin()
    {
        $site = Multisites::inst()->getCurrentSite();

        if ($site && $site->Theme) {
            SSViewer::set_theme($site->Theme);
        }
    }
}
