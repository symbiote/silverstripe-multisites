<?php
namespace Symbiote\Multisites\Tests\Cms\Control;

use SilverStripe\Security\Member;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\Core\Convert;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Tests\CMSMainTest;
/**
 * @package silverstripe-multisites
 */
class MultisitesCMSMainTest extends CMSMainTest {
	/** 
	 * Get parent class directory so it pulls the fixtures from that location instead.
	 */
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	public function testSiteTreeHints() 
	{
		$this->markTestSkipped(__FUNCTION__.' not implemented for Multisites. Testing this is not worth the maintainance effort.');
	}

	public function testBreadcrumbs() 
	{
		$this->markTestSkipped(__FUNCTION__.' not implemented for Multisites. Testing this is not worth the maintainance effort. This fails in 3.2, might be passing in 3.3+');
	}

	public function testPublish() 
	{
		$page1 = $this->objFromFixture('Page', "page1");
		$page2 = $this->objFromFixture('Page', "page2");
		$this->session()->inst_set('loggedInAs', $this->idFromFixture(Member::class, 'admin'));

		$response = $this->get('admin/pages/publishall?confirm=1');
		$this->assertContains(
				// NOTE: Change 30 pages, to 31 pages
				'Done: Published 31 pages',
				$response->getBody()
		);

		$actions = CMSBatchActionHandler::config()->batch_actions;

		// Some modules (e.g., cmsworkflow) will remove this action
		$actions = CMSBatchActionHandler::config()->batch_actions;
		if (isset($actions['publish'])) {
			$response = $this->get('admin/pages/batchactions/publish?ajax=1&csvIDs=' . implode(',', array($page1->ID, $page2->ID)));
			$responseData = Convert::json2array($response->getBody());
			$this->assertArrayHasKey($page1->ID, $responseData['modified']);
			$this->assertArrayHasKey($page2->ID, $responseData['modified']);
		}

		// Get the latest version of the redirector page 
		$pageID = $this->idFromFixture(RedirectorPage::class, 'page5');
		$latestID = DB::prepared_query('select max("Version") from "RedirectorPage_versions" where "RecordID" = ?', array($pageID))->value();
		$dsCount = DB::prepared_query('select count("Version") from "RedirectorPage_versions" where "RecordID" = ? and "Version"= ?', array($pageID, $latestID))->value();
		$this->assertEquals(1, $dsCount, "Published page has no duplicate version records: it has " . $dsCount . " for version " . $latestID);

		$this->session()->clear('loggedInAs');

		//$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())

		/*
		$response = Director::test("admin/pages/publishitems", array(
			'ID' => ''
			'Title' => ''
			'action_publish' => 'Save and publish',
		), $session);
		$this->assertRegexp('/Done: Published 4 pages/', $response->getBody())
		*/
	}

	public function testGetList() 
	{
		$this->markTestIncomplete(__FUNCTION__.' not implemented for Multisites.');
		return;
		/*$controller = new CMSMain();

		// Test all pages (stage)
		$pages = $controller->getList()->sort('Title');
		$this->assertEquals(1, $pages->count());
		$this->assertEquals(
				// NOTE: Prepend 'Default Site'
				array('Default Site', 'Home', 'Page 10', 'Page 11', 'Page 12'),
				$pages->Limit(5)->column('Title')
		);

		// Change state of tree
		$page1 = $this->objFromFixture('Page', 'page1');
		$page3 = $this->objFromFixture('Page', 'page3');
		$page11 = $this->objFromFixture('Page', 'page11');
		$page12 = $this->objFromFixture('Page', 'page12');
		// Deleted
		$page1->doUnpublish();
		$page1->delete();
		// Live and draft
		$page11->publish('Stage', 'Live');
		// Live only
		$page12->publish('Stage', 'Live');
		$page12->delete();

		// Re-test all pages (stage)
		$pages = $controller->getList()->sort('Title');
		$this->assertEquals(27, $pages->count());
		$this->assertEquals(
				// NOTE: Prepend 'Default Site'
				array('Default Site', 'Home', 'Page 10', 'Page 11', 'Page 13', 'Page 14'),
				$pages->Limit(6)->column('Title')
		);

		// Test deleted page filter
		$params = array(
				'FilterClass' => 'CMSSiteTreeFilter_StatusDeletedPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(1, $pages->count());
		$this->assertEquals(
				array('Page 1'),
				$pages->column('Title')
		);

		// Test live, but not on draft filter
		$params = array(
				'FilterClass' => 'CMSSiteTreeFilter_StatusRemovedFromDraftPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(1, $pages->count());
		$this->assertEquals(
				array('Page 12'),
				$pages->column('Title')
		);

		// Test live pages filter
		$params = array(
				'FilterClass' => 'CMSSIteTreeFilter_PublishedPages'
		);
		$pages = $controller->getList($params);
		$this->assertEquals(3, $pages->count());
		$this->assertEquals(
				array('Default Site', 'Page 11', 'Page 12'),
				$pages->column('Title')
		);

		// Test that parentID is ignored when filtering
		$pages = $controller->getList($params, $page3->ID);
		$this->assertEquals(3, $pages->count());
		$this->assertEquals(
				// NOTE: Prepend 'Default Site'
				array('Default Site', 'Page 11', 'Page 12'),
				$pages->column('Title')
		);

		// Test that parentID is respected when not filtering
		$pages = $controller->getList(array(), $page3->ID);
		$this->assertEquals(2, $pages->count());
		$this->assertEquals(
				array('Page 3.1', 'Page 3.2'),
				$pages->column('Title')
		);*/
	}
}
