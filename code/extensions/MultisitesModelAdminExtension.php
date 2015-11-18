<?php
/**
 * MultisitesModelAdminExtension
 *
 * @package silverstripe-multisites
 */
class MultisitesModelAdminExtension extends Extension {
	
	private static $allowed_actions = array(
		'updateSiteFilter'
	);

	
	/**
	 * If this dataClass is MultisitesAware, set the Multisites_ActiveSite 
	 * session variable to one of the follwing:
	 * a) The current site, if the current member is a manager of that site
	 * b) The first site that the current member is a manager of
	 **/
	public function onAfterInit(){	
		if(singleton($this->owner->getList()->dataClass())->hasExtension('MultisitesAware')){
			if(!Session::get('Multisites_ActiveSite')){

				$managedByMember = Multisites::inst()->sitesManagedByMember();
				
				if(count($managedByMember)){
					$currentSiteID = Multisites::inst()->getCurrentSiteId();
					if(in_array($currentSiteID, $managedByMember)){
						$siteID = $currentSiteID;
					}else{
						$siteID = $managedByMember[0];
					}
					Multisites::inst()->setActiveSite($siteID);
				}
			}
		}
	}

	
	/**
	 * If this dataClass is MultisitesAware, filter the list by the current Multisites_ActiveSite
	 **/
	public function updateList(&$list){
		if(singleton($list->dataClass())->hasExtension('MultisitesAware')){
			if($siteID = $this->owner->getRequest()->requestVar('SiteID')){
				Multisites::inst()->setActiveSite($siteID);
			}
			$site = Multisites::inst()->getActiveSite();
			if ($site) {
				$list = $list->filter('SiteID', $site->ID);
			}
		}
	}


	/**
	 * If the current member is not a "Manager" of any sites, they shouldn't be able to manage MultisitesAware DataObjects.
	 **/
	public function updateEditForm($form){
		if(singleton($this->owner->getList()->dataClass())->hasExtension('MultisitesAware')){
			$managedSites = Multisites::inst()->sitesManagedByMember();
			$source = Site::get()->filter('ID', Multisites::inst()->sitesManagedByMember())->map('ID', 'Title')->toArray();
			$plural = singleton($this->owner->modelClass)->plural_name();
			if(!count($source)){
				$form->setFields(FieldList::create(
					LiteralField::create('NotAManager', "You do not have permission to manage $plural on any Sites")
				));
			}
		}
	}


	/**
	 * Provide a Site filter
	 **/
	public function updateSearchForm($form){
		if(singleton($this->owner->getList()->dataClass())->hasExtension('MultisitesAware')){
			$managedSites = Multisites::inst()->sitesManagedByMember();

			$source = Site::get()->filter('ID', Multisites::inst()->sitesManagedByMember())->map('ID', 'Title')->toArray();
			$plural = singleton($this->owner->modelClass)->plural_name();
			if(count($source)){
				$form->Fields()->push(DropdownField::create(
					'SiteID', 
					"Site: ", 
					$source,
					Multisites::inst()->getActiveSite()->ID
				));
			}
		}
	}

}
