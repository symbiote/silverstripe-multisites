<?php
/**
 * MultisitesModelAdminExtension
 *
 * @package silverstripe-multisites
 */
class MultisitesModelAdminExtension extends Extension
{
    
    private static $allowed_actions = array(
        'updateSiteFilter'
    );

    /**
     * Cached instance of the data class currently being listed
     *
     * @var DataObject
     **/
    private $listDataClass;
    
    /**
     * If this dataClass is MultisitesAware, set the Multisites_ActiveSite 
     * session variable to one of the follwing:
     * a) The SiteID passed in the request params, if it exists
     * b) The current site, if the current member is a manager of that site
     * c) The first site that the current member is a manager of
     **/
    public function onAfterInit()
    {
        if ($this->modelIsMultiSitesAware()) {
            if ($siteID = $this->owner->getRequest()->requestVar('SiteID')) {
                $this->setActiveSite($siteID);
            }

            if (!$this->getActiveSite()) {
                $managedByMember = Multisites::inst()->sitesManagedByMember();
                
                if (count($managedByMember)) {
                    $currentSiteID = Multisites::inst()->getCurrentSiteId();
                    if (in_array($currentSiteID, $managedByMember)) {
                        $siteID = $currentSiteID;
                    } else {
                        $siteID = $managedByMember[0];
                    }
                    $this->setActiveSite($siteID);
                }
            }
        }
    }

    
    /**
     * If this dataClass is MultisitesAware, filter the list by the current Multisites_ActiveSite
     **/
    public function updateList(&$list)
    {
        if ($this->modelIsMultiSitesAware()) {
            $site = $this->getActiveSite();
            if ($site) {
                $list = $list->filter('SiteID', $site->ID);
            }
        }
    }


    /**
     * If the current member is not a "Manager" of any sites, they shouldn't be able to manage MultisitesAware DataObjects.
     **/
    public function updateEditForm($form)
    {
        if ($this->modelIsMultiSitesAware()) {
            $managedSites = Multisites::inst()->sitesManagedByMember();
            $source = Site::get()->filter('ID', Multisites::inst()->sitesManagedByMember())->map('ID', 'Title')->toArray();
            $plural = singleton($this->owner->modelClass)->plural_name();
            if (!count($source)) {
                $form->setFields(FieldList::create(
                    LiteralField::create('NotAManager', "You do not have permission to manage $plural on any Sites")
                ));
            }
        }
    }


    /**
     * Provide a Site filter
     **/
    public function updateSearchForm($form)
    {
        if ($this->modelIsMultiSitesAware()) {
            $managedSites = Multisites::inst()->sitesManagedByMember();

            $source = Site::get()->filter('ID', Multisites::inst()->sitesManagedByMember())->map('ID', 'Title')->toArray();
            $plural = singleton($this->owner->modelClass)->plural_name();
            if (count($source)) {
                $activeSite = $this->getActiveSite();
                $form->Fields()->push(DropdownField::create(
                    'SiteID',
                    "Site: ",
                    $source,
                    $activeSite ? $activeSite->ID : null
                ));
            }
        }
    }


    /**
     * get the active site for this model admin
     *
     * @return Site
     **/
    public function getActiveSite()
    {
        if ($this->owner->config()->use_active_site_session) {
            return Multisites::inst()->getActiveSite();
        } else {
            if ($this->modelIsMultiSitesAware()) {
                if ($active = Session::get($this->getActiveSiteSessionKey())) {
                    return Site::get()->byID($active);
                }
            }
        }
    }


    /**
     * Set the active site for this model admin
     *
     * @param int $siteID
     * @return void
     **/
    public function setActiveSite($siteID)
    {
        if ($this->owner->config()->use_active_site_session) {
            Multisites::inst()->setActiveSite($siteID);
        } else {
            Session::set($this->getActiveSiteSessionKey(), $siteID);
        }
    }


    /**
     * Get the key used to store this model admin active site Session to
     *
     * @return string
     **/
    public function getActiveSiteSessionKey()
    {
        return 'Multisites_ActiveSite_For_' . $this->owner->class;
    }


    /**
     * Get and cache an instance of the data class currently being listed
     *
     * @return DataObject
     **/
    private function getListDataClass()
    {
        if (!$this->listDataClass) {
            $this->listDataClass = singleton($this->owner->getSearchContext()->getResults(array())->dataClass());
        }
        return $this->listDataClass;
    }


    /**
     * Determines whether the current model being managed is MultiSitesAware
     *
     * @return boolean
     **/
    private function modelIsMultiSitesAware()
    {
        $model = $this->getListDataClass();
        return $model->hasExtension('MultisitesAware') || $model->is_a('SiteTree');
    }
}
