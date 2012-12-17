<?php
/**
 * @package silverstripe-multisites
 */
class Site extends Page implements HiddenClass {
	
	public static $singular_name = 'Site';
	public static $plural_name = 'Sites';
	public static $description = 'A page type which provides a subsite.';

	public static $db = array(
		'Title'       => 'Varchar(255)',
		'Tagline'     => 'Varchar(255)',
		'Theme'       => 'Varchar(255)',
		'Scheme'      => 'Enum("any, http, https", "any")',
		'Host'        => 'Varchar(100)',
		'HostAliases' => 'MultiValueField',
		'IsDefault'   => 'Boolean',
		'DevID'		  => 'Varchar' // developer identifier
	);

	public static $defaults = array(
		'Scheme' => 'any'
	);

	public static $default_sort = '"Title"';

	public static $searchable_fields = array(
		'Title'     => 'Title',
		'Domain'    => 'Domain',
		'IsDefault' => 'Is Default'
	);

	public static $summary_fields = array(
		'Title'     => 'Title',
		'Url'       => 'URL',
		'IsDefault' => 'Is Default'
	);

	public static $developer_identifiers = array();

	public static $icon = 'multisites/images/world.png';

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

		if(is_array(Multisites::$developer_identifiers)){

			$fields->addFieldToTab('Root.Main', DropdownField::create('DevID', _t(
				'Multisites.DeveloperIdentifier', 'Developer Identifier'),
				Multisites::$developer_identifiers
			));
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

		parent::onBeforeWrite();
	}

	public function onAfterWrite() {
		Multisites::inst()->build();
		parent::onAfterWrite();
	}
	
	/**
	 * Make sure there is a site record.
	 * 
	 * @return type 
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if( !Site::get()->count() ) {

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

}
