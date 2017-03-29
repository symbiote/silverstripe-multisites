<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesSiteTreeTestExtension extends SiteTreeExtension {

	public function validate(ValidationResult $result) {
		// Required to ensure 'Site' record is built during Travis-CI
		$this->setupTest();
	}

	/**
	 * Sets up the 'Site' record in-place while running 'cms/tests' and others.
	 */
	private function setupTest() {
		if (!SapphireTest::is_running_test() || $this->owner instanceof Site) {
			return;
		}
		Multisites::inst()->setupIfInTest();

		$this->owner->SiteID = (int)Multisites::inst()->getDefaultSiteId();
		if (!$this->owner->ParentID) {
			$this->owner->ParentID = $this->owner->SiteID;
		}
	}

	/**
	 * Keep the SiteID field consistent.
	 */
	public function onBeforeWrite() {
		if ($this->owner instanceof Site) {
			return;
		}
		// NOTE: When building fixtures during unit tests, validation is disabled, so we must
		//		 call this function here as well.
		$this->setupTest();
	}
}
