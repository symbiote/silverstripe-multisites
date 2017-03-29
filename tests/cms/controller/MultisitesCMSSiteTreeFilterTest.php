<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesCMSSiteTreeFilterTest extends CMSSiteTreeFilterTest {
	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testSearchFilterByTitle() 
	{
		$page1 = $this->objFromFixture('Page', 'page1');
		$page2 = $this->objFromFixture('Page', 'page2');
	
		$f = new CMSSiteTreeFilter_Search(array('Title' => 'Page 1'));
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($page1));
		$this->assertFalse($f->isPageIncluded($page2));
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			// NOTE: Change ParentID = 0, to ParentID = 1
			array('ID' => $page1->ID, 'ParentID' => 1),
			$results[0]
		);
	}

	public function testChangedPagesFilter() 
	{
		$unchangedPage = $this->objFromFixture('Page', 'page1');
		$unchangedPage->doPublish();
	
		$changedPage = $this->objFromFixture('Page', 'page2');
		$changedPage->Title = 'Original';
		$changedPage->publish('Stage', 'Live');
		$changedPage->Title = 'Changed';
		$changedPage->write();
	
		// Check that only changed pages are returned
		$f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'Changed'));
		$results = $f->pagesIncluded();
	
		$this->assertTrue($f->isPageIncluded($changedPage));
		$this->assertFalse($f->isPageIncluded($unchangedPage));
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			array('ID' => $changedPage->ID, 'ParentID' => 1),
			$results[0]
		);
	
		// Check that only changed pages are returned
		$f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'No Matches'));
		$results = $f->pagesIncluded();
		$this->assertEquals(0, count($results));

		// If we roll back to an earlier version than what's on the published site, we should still show the changed
		$changedPage->Title = 'Changed 2';
		$changedPage->publish('Stage', 'Live');
		$changedPage->doRollbackTo(1);

		$f = new CMSSiteTreeFilter_ChangedPages(array('Term' => 'Changed'));
		$results = $f->pagesIncluded();

		$this->assertEquals(1, count($results));
		$this->assertEquals(array('ID' => $changedPage->ID, 'ParentID' => 1), $results[0]);
	}
}
