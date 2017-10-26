<?php
namespace Symbiote\Multisites\Tests;

use SilverStripe\Assets\Folder;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\Upload;
use SilverStripe\Dev\FunctionalTest;

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
		$initalLocation = Config::inst()->get(Upload::class, 'uploads_folder');
		$this->assertEquals('Uploads', Config::inst()->get(Upload::class, 'uploads_folder'));

		//Reset it to a different value
		Config::inst()->update(Upload::class, 'uploads_folder', 'testFolderLocation');
		$this->assertNotEquals($initalLocation, Config::inst()->get(Upload::class, 'uploads_folder'));

		//Run MultisitesControllerExtension::onAfterInit() and assert it's now set to 'default-site'
		$controller->onAfterInit();
		$this->assertEquals('default-site', Config::inst()->get(Upload::class, 'uploads_folder'));
	}
}
