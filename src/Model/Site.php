<?php

namespace Symbiote\Multisites\Model;

use Symbiote\Multisites\Multisites;

use Symbiote\MultiValueField\Fields\MultiValueTextField;

use Page;
use SilverStripe\Assets\Folder;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\View\Parsers\URLSegmentFilter;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\LiteralField;

/**
 * @package silverstripe-multisites
 */
class Site extends Page implements HiddenClass, PermissionProvider {

    private static $table_name = 'Site';

	private static $singular_name = 'Site';
	private static $plural_name = 'Sites';
	private static $description = 'A page type which provides a subsite.';

	private static $db = array(
		'Tagline'     => 'Varchar(255)',
		'Theme'       => 'Varchar(255)',
		'Scheme'      => 'Enum("any, http, https", "any")',
		'Host'        => 'Varchar(100)',
		'HostAliases' => 'MultiValueField',
		'IsDefault'   => 'Boolean',
		'DevID'       => 'Varchar', // developer identifier
        'RobotsTxt'   => 'Text'
	);

	private static $has_one = array(
		'Folder' => Folder::class
	);

	private static $defaults = array(
		'Scheme' => 'any',
        'RobotsTxt' => ''
	);

	private static $default_sort = '"Title"';

	private static $searchable_fields = array(
		'Title'     => 'Title',
		'Domain'    => 'Domain',
		'IsDefault' => 'Is Default'
	);

	private static $summary_fields = array(
		'Title'     => 'Title',
		'Url'       => 'URL',
		'IsDefault' => 'Is Default'
	);

    private static $available_themes = [];

	private static $icon = 'symbiote/silverstripe-multisites: client/images/world.png';

	public function getCMSFields() {
		$themes = $this->config()->available_themes;
        if (count($themes)) {
            $theme = new DropdownField('Theme', _t('Multisites.THEME', 'Theme'), $themes);
            $theme->setEmptyString(_t('Multisites.DEFAULTTHEME', '(Default theme)'));
        } else {
            $theme = LiteralField::create('ThemeWarning', '<p class="error">No themes configured in Site.available_themes</p>');
        }

		$fields = new FieldList(new TabSet('Root', new Tab(
			'Main',
			new HeaderField('SiteConfHeader', _t('Multisites.SITECONF', 'Site Configuration')),
			new TextField('Title', _t('Multisites.TITLE', 'Title')),
			new TextField('Tagline', _t('Multisites.TAGLINE', 'Tagline/Slogan')),
			$theme,
			new HeaderField('SiteURLHeader', _t('Multisites.SITEURL', 'Site URL')),
			new OptionsetField('Scheme', _t('Multisites.SCHEME', 'Scheme'), array(
				'any'   => _t('Multisites.ANY', 'Any'),
				'http'  => _t('Multisites.HTTP', 'HTTP'),
				'https' => _t('Multisites.HTTPS', 'HTTPS (HTTP Secure)')
			)),
			new TextField('Host', _t('Multisites.HOST', 'Host')),
			new MultiValueTextField(
				'HostAliases',_t('Multisites.HOSTALIASES','Host Aliases')
			),
			new CheckboxField('IsDefault', _t(
				'Multisites.ISDEFAULT', 'Is this the default site?'
			)),
			new HeaderField('SiteAdvancedHeader', _t('Multisites.SiteAdvancedHeader', 'Advanced Settings')),
            TextareaField::create('RobotsTxt', _t('Multisites.ROBOTSTXT', 'Robots.txt'))
            	->setDescription(_t('Multisites.ROBOTSTXTUSAGE', '<p>Please consult <a href="http://www.robotstxt.org/robotstxt.html" target="_blank">http://www.robotstxt.org/robotstxt.html</a> for usage of the robots.txt file.</p>'))
		)));

		$devIDs = Config::inst()->get('Multisites', 'developer_identifiers');
		if(is_array($devIDs)){
			if(!ArrayLib::is_associative($devIDs)) $devIDs = ArrayLib::valuekey($devIDs);
			$fields->addFieldToTab('Root.Main', DropdownField::create('DevID', _t(
				'Multisites.DeveloperIdentifier', 'Developer Identifier'),
				$devIDs
			));
		}

		if(Multisites::inst()->assetsSubfolderPerSite()){
			$fields->addFieldToTab(
				'Root.Main',
				new TreeDropdownField('FolderID', _t('Multisites.ASSETSFOLDER', 'Assets Folder'), Folder::class),
				'SiteURLHeader'
			);
		}

		if(!Permission::check('SITE_EDIT_CONFIGURATION')){
			foreach ($fields->dataFields() as $field) {
				$fields->makeFieldReadonly($field);
			}
		}

		$this->extend('updateSiteCMSFields', $fields);

		return $fields;
	}

	public function getUrl() {
		if($this->Host){
			if(!$this->Scheme || $this->Scheme == 'any') {
				return 'http://' . $this->Host;
			} else {
				return sprintf('%s://%s', $this->Scheme, $this->Host);
			}
		}else{
			return Director::absoluteBaseURL();
		}
	}

	public function AbsoluteLink($action = null){
		return $this->getURL() . '/';
	}

	public function Link($action = null) {
		if ($this->ID && $this->ID == Multisites::inst()->getCurrentSiteId()) {
			return parent::Link($action);
		}
		return Controller::join_links($this->RelativeLink($action));
	}

	public function RelativeLink($action = null) {
		if($this->ID && $this->ID == Multisites::inst()->getCurrentSiteId()) {
			return $action;
		} else {
			return Controller::join_links($this->getUrl(), $action);
		}
	}

	public function onBeforeWrite() {
		$normalise = function($url) {
			return trim(str_replace(array('http://', 'https://'), null, $url), '/');
		};

		$this->Host = $normalise($this->Host);

		if(!is_array($this->HostAliases) && ($aliases = $this->HostAliases->getValue())) {
			$this->HostAliases = array_map($normalise, $aliases);
		}

		if($this->IsDefault) {
			$others = static::get()->where('"SiteTree"."ID" <> ' . $this->ID)->filter('IsDefault', true);

			foreach($others as $other) {
				$other->IsDefault = false;
				$other->write();
			}
		}

		//Set MenuTitle to NULL so that Title is used
		$this->MenuTitle = NULL;

		if($this->ID && Multisites::inst()->assetsSubfolderPerSite() && !$this->Folder()->exists()){
			$this->FolderID = $this->createAssetsSubfolder();
		}

		parent::onBeforeWrite();
	}


	/**
	 * creates a subfolder in assets/ to store this sites files
	 * @param Boolean $write - writes the site object if set to true
	 * @return Int $folder->ID
	 **/
	public function createAssetsSubfolder($write = false){
		$siteFolderName = singleton(URLSegmentFilter::class)->filter($this->Title);
		$folder = Folder::find_or_make($siteFolderName);

		if($write){
			$this->FolderID = $folder->ID;
			$this->write();
			if($this->isPublished()) $this->doPublish();
		}

		return $folder->ID;
	}


	public function onAfterWrite() {
		Multisites::inst()->build();
		parent::onAfterWrite();
	}

	/**
	 * Make sure there is a site record.
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if(Site::get()->count() > 0) {
			return;
		}

		$site = Site::create();
		$site->Title = _t('Multisites.DEFAULTSITE', 'Default Site');
		$site->IsDefault = true;
		$site->FolderID = Folder::find_or_make('default-site')->ID;
		$site->write();
		$site->publish('Stage', 'Live');

		DB::alteration_message('Default site created', 'created');

		$pages = SiteTree::get()->exclude('ID', $site->ID)->filter('ParentID', 0);
		$count = count($pages);
		if ($count > 0) {
			foreach($pages as $page) {
				$page->ParentID = $site->ID;
				$page->write();
				if ($page->isPublished()) {
					$page->publish('Stage', 'Live');
				}
			}

			DB::alteration_message("Moved $count existing pages under new default site.", 'changed');
		}
	}

	/**
	 * Alternative implementation that takes into account the current site
	 * as the root
	 *
	 * @param type $link
	 * @param type $cache
	 * @return boolean
	 */
	static public function get_by_link($link, $cache = true) {
		$current = Multisites::inst()->getCurrentSiteId();

		if(trim($link, '/')) {
			$link = trim(Director::makeRelative($link), '/');
		} else {
			$link = RootURLController::get_homepage_link();
		}

		$parts = Convert::raw2sql(preg_split('|/+|', $link));

		// Grab the initial root level page to traverse down from.
		$URLSegment = array_shift($parts);

		$sitetree   = DataObject::get_one (
			SiteTree::class, "\"URLSegment\" = '$URLSegment' AND \"ParentID\" = " . $current, $cache
		);

		if (!$sitetree) {
			return false;
		}

		/// Fall back on a unique URLSegment for b/c.
		if(!$sitetree && self::nested_urls() && $page = DataObject::get(SiteTree::class, "\"URLSegment\" = '$URLSegment'")->First()) {
			return $page;
		}

		// Check if we have any more URL parts to parse.
		if(!count($parts)) return $sitetree;

		// Traverse down the remaining URL segments and grab the relevant SiteTree objects.
		foreach($parts as $segment) {
			$next = DataObject::get_one (
				SiteTree::class, "\"URLSegment\" = '$segment' AND \"ParentID\" = $sitetree->ID", $cache
			);

			if(!$next) {
				return false;
			}

			$sitetree->destroy();
			$sitetree = $next;
		}

		return $sitetree;
	}


	/**
	 * Get the name of the theme applied to this site, allow extensions to override
	 * @return String
	 **/
	public function getSiteTheme(){
		$theme = $this->Theme;
		if (!$theme) {
			$theme = Config::inst()->get(SSViewer::class, 'theme');
		}
		$this->extend('updateGetSiteTheme', $theme);
		return $theme;
	}


	/**
	 * Checks to see if this site has a feature as defined in Muiltisites.site_features config
	 * @return Boolean
	 **/
	public function hasFeature($feature){
		if(!$this->DevID) return false;

		$sites = Config::inst()->get('Multisites', 'site_features');

		if(!isset($sites[$this->DevID])) return false;

		$features = ArrayLib::valuekey($sites[$this->DevID]);

		if(!isset($features[$feature])) return false;

		return true;
	}


	public function providePermissions() {
		return array(
			'SITE_EDIT_CONFIGURATION' => array(
				'name' => 'Edit Site configuration settings. Eg. Site Title, Host Name etc.',
				'category' => 'Sites',
			)
		);
	}

	/**
	 *	This corrects an issue when duplicating a site, since the parent comes back as a false object.
	 */

	public function Parent() {

		return null;
	}

}
