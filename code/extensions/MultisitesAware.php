<?php
/**
 * MultisitesAware
 *
 * @package silverstripe-multisites
 */
class MultisitesAware extends DataExtension
{

    private static $has_one = array(
        'Site' => 'Site'
    );
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->SiteID) {
            $this->owner->SiteID = Multisites::inst()->getActiveSite()->ID;
        }
    }

    
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('SiteID');
    }


    /**
     * Check to see if the current user has permission to edit this MultisitesAware object
     * On the site this object is associated with.
     * @return boolean|null
     **/
    public function canEdit($member = null)
    {
        $managedSites = Multisites::inst()->sitesManagedByMember();
        if (count($managedSites) && in_array($this->owner->SiteID, $managedSites)) {
            // member has permission to manage MultisitesAware objects on this site, 
            // hand over to the object's canEdit method 
            return null;
        } else {
            // member does not have permission to edit objects on this object's Site
            return false;
        }
    }
}
