<?php

class MultisitesControllerExtensionTest extends FunctionalTest {
	
	protected static $fixture_file = 'MultisitesControllerExtensionTest.yml';
	protected static $use_draft_site = true;
	protected static $disable_themes = true;

	
	public function testOnAfterInit() {
		$site = new Multisites();
		$site->setupIfInTest();
		$site->FolderID = Folder::find_or_make('default-site')->ID;
		$controller = ContentController::create();

		// The current location is should be 'Uploads' as onAfterInit() hasn't run
		$initalLocation = Config::inst()->get('Upload', 'uploads_folder');
		$this->assertEquals('Uploads', Config::inst()->get('Upload', 'uploads_folder'));

		//Reset it to a different value
		Config::inst()->update('Upload', 'uploads_folder', 'testFolderLocation');
		$this->assertNotEquals($initalLocation, Config::inst()->get('Upload', 'uploads_folder'));

		//Run MultisitesControllerExtension::onAfterInit() and assert it's now set to 'default-site'
		$controller->onAfterInit();
		$this->assertEquals('default-site', Config::inst()->get('Upload', 'uploads_folder'));
	}
}
