<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesSiteTreeExtension extends SiteTreeExtension {

	public static $has_one = array(
		'Site' => 'Site'
	);

	/**
	 * @return Site
	 */
	public function CurrentSite() {
		return Multisites::inst()->getCurrentSite();
	}

	/**
	 * @return Site
	 */
	public function DefaultSite() {
		return Multisites::inst()->getDefaultSite();
	}

	public function updateCMSFields(FieldList $fields) {
		if($this->owner->ParentID) {
			$url = $this->owner->Parent()->AbsoluteLink();
		} else {
			$url = Director::absoluteBaseURL();
		}

		if(strlen($url) > 36) {
			$url = '...' . substr($url, -32);
		}

		$fields->dataFieldByName('URLSegment')->setURLPrefix($url);
	}
	
	/**
	 * Make sure site home pages are loaded at the root of the site.
	 */
	public function contentcontrollerInit($controller) {
		
		// If we've accessed the homepage as /home/, then we should redirect to /.
		if($controller->dataRecord && $controller->dataRecord instanceof SiteTree
			 	&& MultisitesRootController::should_be_on_root($controller->dataRecord) && (!isset($controller->urlParams['Action']) || !$controller->urlParams['Action'] ) 
				&& !$_POST && !$_FILES && !$controller->redirectedTo() ) {
			$getVars = $_GET;
			unset($getVars['url']);
			if($getVars) $url = "?" . http_build_query($getVars);
			else $url = "";
			$controller->redirect($url, 301);
			return;
		}
	}

	/**
	 * Keep the SiteID field consistent.
	 */
	public function onBeforeWrite() {
		// Set the SiteID for all new pages.
		if(!$this->owner->ID) {
			if($parent = $this->owner->Parent()) {
				if($parent instanceof Site) {
					$this->owner->SiteID = $parent->ID;
				} else {
					$this->owner->SiteID = $parent->SiteID;
				}
			} else {
				$this->owner->SiteID = Multisites::inst()->getDefaultSiteId();
				$this->owner->ParentID = $this->owner->SiteID;
			}
		}
		
		// Make sure SiteID is changed when site tree is reorganised.
		if ($this->owner->ID && !($this->owner instanceof Site) && $this->owner->isChanged('ParentID')) {
			// Get the new parent
			$parent = DataObject::get_by_id('SiteTree', $this->owner->ParentID);
			
			// Make sure the parent exists
			if ( $parent ) {
				// Recursively change SiteID for this and all child pages
				$siteId = ($parent instanceof Site) ? $parent->ID : $parent->SiteID;
				$this->owner->updateSiteID($siteId);
			}	
		}
	}
	
	/**
	 * Recursively update the site ID for this page and all child pages. This writes decendents to the
	 * database, but does not write the current page as it is called from {@link onBeforeWrite}
	 * 
	 * @todo This will mark all child pages as modified. Should it write directly to the database to avoid the publishing workflow?
	 * 
	 * @param int $new The new SiteID
	 */
	public function updateSiteID($new) {
		$this->owner->SiteID = $new;
		if ($this->owner->isChanged('SiteID')) foreach ($this->owner->Children() as $child) {
			$child->updateSiteID($new);
			$child->write();
		}
	}

	public function alternateAbsoluteLink($action = null) {
		if($this->owner->SiteID && $this->owner->SiteID == Multisites::inst()->getCurrentSiteId()) {
			return Director::absoluteURL($this->owner->Link($action));
		} else {
			return $this->owner->RelativeLink($action);
		}
	}

}
