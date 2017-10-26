<?php
namespace Symbiote\Multisites\Tests\Cms\Model;

use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Tests\SiteTreeBrokenLinksTest;
/**
 * @package cms
 * @subpackage tests
 */
class MultisitesSiteTreeBrokenLinksTest extends SiteTreeBrokenLinksTest {
	protected function getCurrentAbsolutePath() 
	{
		$filename = self::$test_class_manifest->getItemPath(get_parent_class($this));
		if(!$filename) throw new LogicException("getItemPath returned null for " . get_parent_class($this));
		return dirname($filename);
	}

	/**
	 * NOTE: Commented out references to VirtualPage as that causes failures in Travis CI
	 */
	public function testRestoreFixesBrokenLinks() 
	{
		// Create page and virtual page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->doPublish());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link,id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->doPublish());

		// Virtual pages are another
		//$vp = new VirtualPage();
		//$vp->CopyContentFromID = $p->ID;
		//$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->doPublish());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		//$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Unpublish the source page, confirm that the page 2 and RP has a broken link on published
		$p->doUnpublish();
		$p2Live = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertEquals(1, $p2Live->HasBrokenLink);
		$this->assertEquals(1, $rpLive->HasBrokenLink);

		// Delete the source page, confirm that the VP, RP and page 2 have broken links on draft
		$p->delete();
		//$vp->flushCache();
		//$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		//$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Restore the page to stage, confirm that this fixes the links
		$p = Versioned::get_latest_version(SiteTree::class, $pageID);
		$p->doRestoreToStage();

		$p2->flushCache();
		$p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
		//$vp->flushCache();
		//$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		//$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

		// Publish and confirm that the p2 and RP broken links are fixed on published
		$this->assertTrue($p->doPublish());
		$p2Live = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $p2->ID);
		$rpLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $rp->ID);
		$this->assertFalse((bool)$p2Live->HasBrokenLink);
		$this->assertFalse((bool)$rpLive->HasBrokenLink);
		
	}
	
	/**
	 * NOTE: Commented out references to VirtualPage as that causes failures in Travis CI
	 */
	public function testRevertToLiveFixesBrokenLinks() 
	{
		// Create page and virutal page
		$p = new Page();
		$p->Title = "source";
		$p->write();
		$pageID = $p->ID;
		$this->assertTrue($p->doPublish());

		// Content links are one kind of link to pages
		$p2 = new Page();
		$p2->Title = "regular link";
		$p2->Content = "<a href=\"[sitetree_link,id=$p->ID]\">test</a>";
		$p2->write();
		$this->assertTrue($p2->doPublish());

		// Virtual pages are another
		//$vp = new VirtualPage();
		//$vp->CopyContentFromID = $p->ID;
		//$vp->write();

		// Redirector links are a third
		$rp = new RedirectorPage();
		$rp->Title = "redirector";
		$rp->LinkType = 'Internal';
		$rp->LinkToID = $p->ID;
		$rp->write();
		$this->assertTrue($rp->doPublish());

		// Confirm that there are no broken links to begin with
		$this->assertFalse($p2->HasBrokenLink);
		//$this->assertFalse($vp->HasBrokenLink);
		$this->assertFalse($rp->HasBrokenLink);

		// Delete from draft and confirm that broken links are marked
		$pID = $p->ID;
		$p->delete();
		
		//$vp->flushCache();
		//$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$p2->flushCache();
		$p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
		$this->assertEquals(1, $p2->HasBrokenLink);
		//$this->assertEquals(1, $vp->HasBrokenLink);
		$this->assertEquals(1, $rp->HasBrokenLink);

		// Call doRevertToLive and confirm that broken links are restored
		$pLive = Versioned::get_one_by_stage(SiteTree::class, 'Live', '"SiteTree"."ID" = ' . $pID);
		$pLive->doRevertToLive();

		$p2->flushCache();
		$p2 = DataObject::get_by_id(SiteTree::class, $p2->ID);
		//$vp->flushCache();
		//$vp = DataObject::get_by_id('SiteTree', $vp->ID);
		$rp->flushCache();
		$rp = DataObject::get_by_id(SiteTree::class, $rp->ID);
		$this->assertFalse((bool)$p2->HasBrokenLink);
		//$this->assertFalse((bool)$vp->HasBrokenLink);
		$this->assertFalse((bool)$rp->HasBrokenLink);

	}
}

