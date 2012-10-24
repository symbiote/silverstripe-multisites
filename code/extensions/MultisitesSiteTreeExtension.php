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

}
