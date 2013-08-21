<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesContentControllerExtension extends Extension {


	/**
	 * Sets the theme to the current site theme
	 **/
	public function onAfterInit() {
		$site = Multisites::inst()->getCurrentSite();

		if($site && $site->Theme) {
			SSViewer::set_theme($site->Theme);
		}
	}


	/**
	 * This method should be used to built navigation menus in templates, 
	 * instead of ContentController->getMenu()
	 * @return ArrayList
	 **/
	public function getSiteMenu($level = 1) {
		$site = Multisites::inst()->getCurrentSite();
		$page = $this->owner->data();
		$result = new ArrayList();

		if($level == 1) {
			$pages = SiteTree::get()->filter(array(
				'ParentID' => $site ? $site->ID : 0,
				'ShowInMenus' => true
			));
		} else {
			$parent = $page;
			$stack = array($page);

			while(($parent = $parent->Parent()) && !($parent instanceof Site)) {
				array_unshift($stack, $parent);
			}

			if(!isset($stack[$level - 2])) {
				return;
			}

			$pages = $stack[$level - 2]->Children();
		}

		foreach($pages as $page) {
			if($page->canView()) $result->push($page);
		}

		return $result;
	}
	
	/**
	 * Make sure a call to Site from templates always returns a valid Site object.
	 * @return Site The current site.
	 */
	public function Site() {
		return $this->owner->dataRecord->getSite();
	}

}
