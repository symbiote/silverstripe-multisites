<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\Core\Extension;

/**
 * Extend the SiteConfig
 */
class MultisitesSiteConfigExtension extends Extension
{

    /**
     * Modify fields in the site config screen to remove fields which are now handled by Site.
     * 
     * @param FieldList $fields The SiteConfig fields
     */
    public function updateCMSFields($fields)
    {
        $fields->removeByName('Tagline');
        $fields->removeByName('Theme');
    }
}