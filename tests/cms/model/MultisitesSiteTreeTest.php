<?php

/**
 * @package silverstripe-multisites
 */
class MultisitesSiteTreeTest extends SiteTreeTest {
	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testCreateDefaultpages() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
		return;
	}

	public function testChidrenOfRootAreTopLevelPages() 
	{
		$pages = SiteTree::get();
		foreach($pages as $page) $page->publish('Stage', 'Live');
		unset($pages);

		/* Get Site object */
		$obj = Site::get()->first();
		/* Then its children should be the top-level pages */
		$stageChildren = $obj->stageChildren()->map('ID','Title');
		$liveChildren = $obj->liveChildren()->map('ID','Title');
		$allChildren = $obj->AllChildrenIncludingDeleted()->map('ID','Title');

		$this->assertContains('Home', $stageChildren);
		$this->assertContains('Products', $stageChildren);
		$this->assertNotContains('Staff', $stageChildren);

		$this->assertContains('Home', $liveChildren);
		$this->assertContains('Products', $liveChildren);
		$this->assertNotContains('Staff', $liveChildren);

		$this->assertContains('Home', $allChildren);
		$this->assertContains('Products', $allChildren);
		$this->assertNotContains('Staff', $allChildren);
	}

	public function testCanSaveBlankToHasOneRelations() 
	{
		$this->markTestSkipped(__FUNCTION__.' not implemented for Multisites. This is because ParentID cannot be 0 with Multisites, so the test will fall over.');
	}

	public function testGetByLink() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites. SiteTree::get_by_link() does not give expected results with Multisites.');
	}

	public function testURLGeneration() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testPageLevel() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testValidURLSegmentURLSegmentConflicts() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testCanBeRoot() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testGetBreadcrumbItems() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}
}
