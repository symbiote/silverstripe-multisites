<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesContentControllerTest extends ContentControllerTest {
	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testViewDraft()
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites. This gets an error.');
	}

	public function testNestedPages()
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testChildrenOf()
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}
}