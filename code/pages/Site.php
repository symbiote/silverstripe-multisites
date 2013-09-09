<?php
/**
 * @package silverstripe-multisites
 */
class Site extends Page implements HiddenClass {
	
	private static $singular_name = 'Site';
	private static $plural_name = 'Sites';
	private static $description = 'A page type which provides a subsite.';

	private static $db = array(
		'Title'       => 'Varchar(255)',
		'Tagline'     => 'Varchar(255)',
		'Theme'       => 'Varchar(255)',
		'Scheme'      => 'Enum("any, http, https", "any")',
		'Host'        => 'Varchar(100)',
		'HostAliases' => 'MultiValueField',
		'IsDefault'   => 'Boolean',
		'DevID'       => 'Varchar' // developer identifier
	);

	private static $has_one = array(
		'Folder' => 'Folder'
	);

	private static $defaults = array(
		'Scheme' => 'any'
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


	private static $icon = 'multisites/images/world.png';

	public function getCMSFields() {
		$conf = SiteConfig::current_site_config();
		$themes = $conf->getAvailableThemes();

		$theme = new DropdownField('Theme', _t('Multisites.THEME', 'Theme'), $themes);
		$theme->setEmptyString(_t('Multisites.DEFAULTTHEME', '(Default theme)'));

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
			))
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
				new TreeDropdownField('FolderID', _t('Multisites.ASSETSFOLDER', 'Assets Folder'), 'Folder'), 
				'SiteURLHeader'
			);
		}

		$this->extend('updateSiteCMSFields', $fields);

		return $fields;
	}

	public function getUrl() {
		if(!$this->Scheme || $this->Scheme == 'any') {
			return 'http://' . $this->Host;
		} else {
			return sprintf('%s://%s', $this->Scheme, $this->Host);
		}
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
			return Controller::join_links($this->getUrl(), Director::baseURL(), $action);
		}
	}

	protected function onBeforeWrite() {
		$normalise = function($url) {
			return trim(str_replace(array('http://', 'https://'), null, $url), '/');
		};

		$this->Host = $normalise($this->Host);

		if($aliases = $this->HostAliases->getValue()) {
			$this->HostAliases = array_map($normalise, $aliases);
		}

		if($this->IsDefault) {
			$others = static::get()->where('"SiteTree"."ID" <> ' . $this->ID)->filter('IsDefault', true);

			foreach($others as $other) {
				$other->IsDefault = false;
				$other->write();
			}
		}

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
		$siteFolderName = singleton('URLSegmentFilter')->filter($this->Title);
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

		if(!Site::get()->count()) {
			$site = new Site();
			$site->Title = _t('Multisites.DEFAULTSITE', 'Default Site');
			$site->IsDefault = true;
			$site->write();

			DB::alteration_message('Default site created', 'created');

			$pages = SiteTree::get()->exclude('ID', $site->ID)->filter('ParentID', 0);
			$count = count($pages);

			foreach($pages as $page) {
				$page->ParentID = $site->ID;
				$page->write();
				$page->publish('Stage', 'Live');
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
			'SiteTree', "\"URLSegment\" = '$URLSegment' AND \"ParentID\" = " . $current, $cache
		);
		
		/// Fall back on a unique URLSegment for b/c.
		if(!$sitetree && self::nested_urls() && $page = DataObject::get('SiteTree', "\"URLSegment\" = '$URLSegment'")->First()) {
			return $page;
		}

		// Check if we have any more URL parts to parse.
		if(!count($parts)) return $sitetree;

		// Traverse down the remaining URL segments and grab the relevant SiteTree objects.
		foreach($parts as $segment) {
			$next = DataObject::get_one (
				'SiteTree', "\"URLSegment\" = '$segment' AND \"ParentID\" = $sitetree->ID", $cache
			);
			
			if(!$next) {
				return false;
			}

			$sitetree->destroy();
			$sitetree = $next;
		}

		return $sitetree;
	}

}
