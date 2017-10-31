<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\CMS\Model\SiteTreeExtension;

/**
 * Publishes separate static error pages for each site.
 *
 * @package silverstripe-multisites
 */
class MultisitesErrorPageExtension extends SiteTreeExtension
{
    public function updateErrorFilename(&$name, $statusCode) {
        $insert = '';

        if ($site = Multisites::inst()->getActiveSite()) {
            $name = str_replace('error-', 'error-' . $site->Host . '-', $name);
        }
    }
}