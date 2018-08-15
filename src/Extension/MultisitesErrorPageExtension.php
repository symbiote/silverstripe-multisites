<?php
namespace Symbiote\Multisites\Extension;

use Symbiote\Multisites\Multisites;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;

/**
 * Publishes separate static error pages for each site.
 * Also prevents publishing of error pages on domains that the 
 * user isn't logged into.
 *
 * @package silverstripe-multisites
 */
class MultisitesErrorPageExtension extends Extension
{
    public function __construct() {
        Requirements::css("symbiote/silverstripe-multisites: client/css/MultisitesErrorPage.css");
    }

    public function updateErrorFilename(&$name, $statusCode)
    {
        if ($site = Multisites::inst()->getActiveSite()) {
            $name = str_replace('error-', 'error-'.$site->Host.'-', $name);
        }
    }

    public function canPublish($member = null)
    {
        //only allow publish if user logged into page's domain.
        $parent = $this->owner->parent;
        $siteID = $parent ? $parent->ID : null;
        $multiID = Multisites::inst()->getCurrentSiteId();

        if ($siteID == $multiID) {
            return true;
        }

        //this actually removes the publish btn
        return false;
    }

    public function updateCMSFields($fields)
    {
        //if cant publish (see canPublish)
        if (!$this->canPublish()) {
            //attach message above page title informing authors
            $url = $this->owner->parent->Link();
            $nopubmsg = new LiteralField(
                "nopubmsg",
                '<div class="MultisitesErrorPage_NoPubMsg">To publish this page, you need to log in via: <em>' . $url . '</em></div>'
            );
            $fields->insertBefore("Title", $nopubmsg);
        }
    }

    public function updateCMSActions($actions)
    {
        //if cant publish (see canPublish)
        if (!$this->canPublish()) {
            //mimic publish button with a disabled appearance
            $nopubbtn = new LiteralField(
                "nopubbtn",
                '<div class="MultisitesErrorPage_NoPubBtn font-icon-rocket"> Publish</div>'
            );
            $actions->insertAfter("action_save", $nopubbtn);
        }
    }
}