<?php

/**
 * Tests link tracking to files and images.
 */
class MultisitesFileLinkTrackingTest extends FileLinkTrackingTest {
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testFileLinkRewritingOnVirtualPages() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites. VirtualPage tests cause odd errors on Travis CI.');
	}
}
