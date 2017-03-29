<?php

class MultisitesSiteTreeBacklinksTest extends SiteTreeBacklinksTest {
	/**
	 * Nulled out to stop RelativeLink() loop error.
	 */
	protected static $fixture_file = null;

	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function setUp() {
		parent::setUp();
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites. SiteTree->RelativeLink() loop occurs due to fixture_file.');
	}
}