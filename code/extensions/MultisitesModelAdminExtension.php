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
	 * If this dataClass is MultisitesAware, set the MultisitesModelAdmin_SiteID 
	 * session variable to one of the follwing:
	 * a) The current site, if the current member is a manager of that site
	 * b) The first site that the current member is a manager of
	 **/
	public function onAfterInit(){	
		if(singleton($this->owner->getList()->dataClass())->hasExtension('MultisitesAware')){
			if(!Session::get('MultisitesModelAdmin_SiteID')){

				$managedByMember = Multisites::inst()->sitesManagedByMember();
				
				if(count($managedByMember)){
					$currentSiteID = Multisites::inst()->getCurrentSiteId();
					if(in_array($currentSiteID, $managedByMember)){
						$siteID = $currentSiteID;
					}else{
						$siteID = $managedByMember[0];
					}
					Session::set('MultisitesModelAdmin_SiteID', $siteID);	
				}
			}
		}
	}

	
	/**
	 * If this dataClass is MultisitesAware, filter the list by the current MultisitesModelAdmin_SiteID
	 **/
	public function updateList(&$list){
		if(singleton($list->dataClass())->hasExtension('MultisitesAware')){
			if($siteID = $this->owner->getRequest()->requestVar('SiteID')){
				Session::set('MultisitesModelAdmin_SiteID', $siteID);		
			}
			$list = $list->filter('SiteID', Session::get('MultisitesModelAdmin_SiteID'));
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
					Session::get('MultisitesModelAdmin_SiteID')
				));
			}
		}
	}

}
