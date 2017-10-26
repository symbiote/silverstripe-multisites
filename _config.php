<?php

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\DebugView;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Reports\EmptyPagesReport;
use SilverStripe\CMS\Reports\BrokenRedirectorPagesReport;
use SilverStripe\CMS\Reports\BrokenVirtualPagesReport;
use SilverStripe\CMS\Reports\RecentlyEditedReport;
use SilverStripe\CMS\Reports\BrokenLinksReport;
use SilverStripe\CMS\Reports\BrokenFilesReport;
use SilverStripe\Reports\Report;
/**
 * @package silverstripe-multisites
 */

if(!ClassInfo::exists('MultiValueField')) {
	$view = new DebugView();
	$link = 'https://github.com/nyeholt/silverstripe-multivaluefield';

	if(!headers_sent()) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
	}

	$view->writeHeader();
	$view->writeInfo('Dependency Error', 'The Multisites module requires the MultiValueField module.');
	$view->writeParagraph("Please install the <a href=\"$link\">MultiValueField</a> module.");
	$view->writeFooter();

	exit;
}

if(!defined('MULTISITES_PATH')) define('MULTISITES_PATH', rtrim(basename(dirname(__FILE__))));

CMSMenu::remove_menu_item('MultisitesCMSSiteAddController');
CMSMenu::remove_menu_item('MultisitesCMSPageAddController');

LeftAndMain::require_css(MULTISITES_PATH . '/css/MultisitesAdmin.css');
LeftAndMain::require_javascript(MULTISITES_PATH . '/javascript/MultisitesAdmin.js');

// Remove LeftAndMain.AddForm.js - at least until this ticket is resolved
// http://open.silverstripe.org/ticket/7987
Requirements::block(FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.AddForm.js');

Report::add_excluded_reports(array(
    EmptyPagesReport::class,
    BrokenRedirectorPagesReport::class,
    BrokenVirtualPagesReport::class,
    RecentlyEditedReport::class,
	EmptyPagesReport::class,
	BrokenLinksReport::class,
	RecentlyEditedReport::class,
	BrokenLinksReport::class,
	BrokenFilesReport::class,
	BrokenVirtualPagesReport::class,
	BrokenRedirectorPagesReport::class
));