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

	public function onBeforeWrite() {
		if(!$this->owner->SiteID) {
			if($parent = $this->owner->Parent()) {
				if($parent instanceof Site) {
					$this->owner->SiteID = $parent->ID;
				} else {
					$this->owner->SiteID = $parent->SiteID;
				}
			} else {
				$this->owner->SiteID = Multisites::inst()->getDefaultSiteId();
			}
		}
	}

	public function alternateAbsoluteLink($action = null) {
		if($this->owner->SiteID && $this->owner->SiteID == Multisites::inst()->getCurrentSiteId()) {
			return Director::absoluteURL($this->owner->Link($action));
		} else {
			return $this->owner->RelativeLink($action);
		}
	}
	
	/**
	 * Returns the current site object in case this is a fake page (like in the case of  pages served 
	 * by the {@link Security} controller)
	 * 
	 * @return Site
	 */
	public function getSite() {
		$site = $this->owner->getComponent('Site');
		return ($site->ID) ? $site : Multisites::inst()->getCurrentSite();
	}

}
