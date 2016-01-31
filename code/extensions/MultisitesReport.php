<?php

/**
 * Override default reports to provide columns and filters that help the user identify which site the 
 * report or page being reported on is associated with
 * @package multisites
 * @author shea@silverstripe.com.au
 **/
class MultisitesReport extends Extension{

	public function updateCMSFields(FieldList $fields){
		$gfc = $fields->fieldByName('Report')->getConfig();
		$columns = $this->owner->columns();
		$exportColumns = array();
		foreach ($columns as $k => $v) {
			$exportColumns[$k] = $v['title'];
		}
		$gfc->getComponentByType('GridFieldExportButton')->setExportColumns($exportColumns);
	}

	public static function getMultisitesReportColumns(){
		return array(
			"Title" => array(
				"title" => "Title", 
				"link" => true,
			),
			"Site.Title" => array(
				"title" => "Site"
			),
			"AbsoluteLink" => array(
				"title" => "URL",
				"link" => true
			)
		);
	}

	public static function getSiteParameterField(){
		$source = Site::get()->map('ID', 'Title')->toArray();
		$source = array('0' => 'All') + $source; // works around ajax bug
		return DropdownField::create('Site', 'Site', $source)->setHasEmptyDefault(false);
	}
}

class Multisites_SideReport_EmptyPages extends SideReport_EmptyPages{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns();
	}

	public function parameterFields() {
		$fields = FieldList::create();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}


	public function sourceRecords($params = null) {
		$records = parent::sourceRecords($params);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}

class Multisites_BrokenLinksReport extends BrokenLinksReport{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns() + parent::columns();
	}

	public function parameterFields() {
		$fields = parent::ParameterFields();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}

	public function sourceRecords($params, $sort, $limit) {
		$records = parent::sourceRecords($params, $sort, $limit);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}


class Multisites_SideReport_RecentlyEdited extends SideReport_RecentlyEdited{
	public function columns() {
		$columns = MultisitesReport::getMultisitesReportColumns();
		$columns['LastEdited'] = array(
			"title" => "Last Edited",
		);
		$columns['LastEdited'] = array(
			"title" => "Last Edited",
		);
		return $columns;
	}

	public function parameterFields() {
		$fields = FieldList::create();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}


	public function sourceRecords($params = null) {
		$records = parent::sourceRecords($params);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}


class Multisites_SideReport_BrokenLinks extends SideReport_BrokenLinks{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns();
	}

	public function parameterFields() {
		$fields = FieldList::create();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}

	public function sourceRecords($params, $sort, $limit) {
		$records = parent::sourceRecords($params, $sort, $limit);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}


class Multisites_SideReport_BrokenFiles extends SideReport_BrokenFiles{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns();
	}

	public function parameterFields() {
		$fields = FieldList::create();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}

	public function sourceRecords($params = null) {
		$records = parent::sourceRecords($params);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}


class Multisites_SideReport_BrokenVirtualPages extends SideReport_BrokenVirtualPages{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns();
	}

	public function parameterFields() {
		$fields = parent::getParameterFields();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}

	public function sourceRecords($params = null) {
		$records = parent::sourceRecords($params);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}


class Multisites_SideReport_BrokenRedirectorPages extends SideReport_BrokenRedirectorPages{
	public function columns() {
		return MultisitesReport::getMultisitesReportColumns();
	}

	public function parameterFields() {
		$fields = parent::getParameterFields();
		$fields->push(MultisitesReport::getSiteParameterField());
		return $fields;
	}

	public function sourceRecords($params = null) {
		$records = parent::sourceRecords($params);
		$site = isset($params['Site']) ? (int)$params['Site'] : 0;
		if($site > 0){
			$records = $records->filter('SiteID', $site);
		}
		return $records;
	}
}
