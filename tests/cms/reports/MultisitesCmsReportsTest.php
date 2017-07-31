<?php
/**
 * @package cms
 * @subpackage tests
 */

class MultisitesCmsReportsTest extends CmsReportsTest {
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testBrokenVirtualPages() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites. VirtualPage tests cause odd errors on Travis CI.');
	}
}
