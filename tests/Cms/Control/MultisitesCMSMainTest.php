<?php

namespace Symbiote\Multisites\Tests\Cms\Control;

use SilverStripe\Security\Member;
use SilverStripe\Admin\CMSBatchActionHandler;
use SilverStripe\Core\Convert;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Core\Manifest\ClassLoader;

/**
 * @package silverstripe-multisites
 */
class MultisitesCMSMainTest extends FunctionalTest
{
    protected static $fixture_file = 'CMSMainTest.yml';

    static protected $orig = array();

    public function setUp()
    {
        parent::setUp();

        // Clear automatically created siteconfigs (in case one was created outside of the specified fixtures).
        $ids = $this->allFixtureIDs(SiteConfig::class);
        if ($ids) {
            foreach (SiteConfig::get()->exclude('ID', $ids) as $config) {
                $config->delete();
            }
        }
    }

    /**
     * Get parent class directory so it pulls the fixtures from that location instead.
     */
    protected function getCurrentAbsolutePath()
    {
        $filename = ClassLoader::inst()->getItemPath(\SilverStripe\CMS\Tests\Controllers\CMSMainTest::class);
        if (!$filename) throw new LogicException("getItemPath returned null for ".get_parent_class($this));
        return dirname($filename);
    }

    public function testPublish()
    {
        $page1 = $this->objFromFixture('Page', "page1");
        $page2 = $this->objFromFixture('Page', "page2");
        $this->session()->set('loggedInAs', $this->idFromFixture(Member::class, 'admin'));

        $response = $this->get('admin/pages/publishall?confirm=1');
        $this->assertContains(
            // NOTE: Change 30 pages, to 31 pages
            'Done: Published 31 pages', $response->getBody()
        );

        $actions = CMSBatchActionHandler::config()->batch_actions;

        // Some modules (e.g., cmsworkflow) will remove this action
        $actions = CMSBatchActionHandler::config()->batch_actions;
        if (isset($actions['publish'])) {
            $response     = $this->get('admin/pages/batchactions/publish?ajax=1&csvIDs='.implode(',',
                    array($page1->ID, $page2->ID)));
            $responseData = Convert::json2array($response->getBody());
            $this->assertArrayHasKey($page1->ID, $responseData['modified']);
            $this->assertArrayHasKey($page2->ID, $responseData['modified']);
        }

        // Get the latest version of the redirector page
        $pageID   = $this->idFromFixture(RedirectorPage::class, 'page5');
        $latestID = DB::prepared_query('select max("Version") from "RedirectorPage_versions" where "RecordID" = ?',
                array($pageID))->value();
        $dsCount  = DB::prepared_query('select count("Version") from "RedirectorPage_versions" where "RecordID" = ? and "Version"= ?',
                array($pageID, $latestID))->value();
        $this->assertEquals(1, $dsCount,
            "Published page has no duplicate version records: it has ".$dsCount." for version ".$latestID);

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
}