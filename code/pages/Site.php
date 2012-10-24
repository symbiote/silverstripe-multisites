<?php
/**
 * @package silverstripe-multisites
 */
class Site extends Page implements HiddenClass {

	public static $db = array(
		'Title'       => 'Varchar(255)',
		'Tagline'     => 'Varchar(255)',
		'Theme'       => 'Varchar(255)',
		'Scheme'      => 'Enum("any, http, https", "any")',
		'Host'        => 'Varchar(100)',
		'HostAliases' => 'MultiValueField',
		'IsDefault'   => 'Boolean',
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

	public function RelativeLink($action = null) {
		if($this->ID && $this->ID == Multisites::inst()->getCurrentSiteId()) {
			return $action;
		} else {
			return Controller::join_links($this->getUrl(), '/', $action);
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

}
