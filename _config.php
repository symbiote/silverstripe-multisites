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

CMSMenu::remove_menu_item('MultisitesCMSSiteAddController');
CMSMenu::remove_menu_item('MultisitesCMSPageAddController');
CMSMenu::remove_menu_item('CMSSettingsController');

LeftAndMain::require_css('multisites/css/MultisitesAdmin.css');

SiteTree::set_create_default_pages(false);
