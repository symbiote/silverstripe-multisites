<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesSiteTreePermissionsTest extends SiteTreePermissionsTest {
	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}
	
	
	public function testAccessingStageWithBlankStage() {
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}
	
	public function testRestrictedViewLoggedInUsers() {
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}
	
	public function testRestrictedViewOnlyTheseUsers() {
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}

	public function testRestrictedViewInheritance() {
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
	}
}
