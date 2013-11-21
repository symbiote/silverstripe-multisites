<?php
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

SiteTree::set_create_default_pages(false);

// Remove LeftAndMain.AddForm.js - at least until this ticket is resolved
// http://open.silverstripe.org/ticket/7987
Requirements::block(FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.AddForm.js');

SS_Report::add_excluded_reports(array(
	'SideReport_EmptyPages',
	'BrokenLinksReport',
	'SideReport_RecentlyEdited',
	'SideReport_BrokenLinks',
	'SideReport_BrokenFiles',
	'SideReport_BrokenVirtualPages',
	'SideReport_BrokenRedirectorPages'
));