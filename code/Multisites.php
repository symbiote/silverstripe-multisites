<?php
/**
 * Contains various multisites utility functions, should be accessed via the
 * {@link inst} method in most cases.
 *
 * @package silverstripe-multisites
 */
class Multisites {

	const CACHE_KEY = 'multisites_map';

	private static $inst;

	protected $cache;
	protected $map;

	protected $default;
	protected $currentId;
	protected $current;
	protected $assetsFolder;

	/**
	 * @return Multisites
	 */
	public static function inst() {
		if(!self::$inst) {
			self::$inst = new self();
			self::$inst->init();
		}

		return self::$inst;
	}

	public function __construct() {
		$this->cache = SS_Cache::factory('Multisites', 'Core', array(
			'automatic_serialization' => true
		));
	}

	/**
	 * Attempts to load the hostname map from the cache, and rebuilds it if
	 * it cannot be loaded.
	 */
	public function init() {
		$cached = $this->cache->load(self::CACHE_KEY);
		$valid  = $cached && isset($cached['hosts']);

		if($valid) {
			$this->map = $cached;
		} else {
			$this->build();
		}
	}

	/**
	 * Builds a map of hostnames to sites, and writes it to the cache.
	 */
	public function build() {
		$this->map = array(
			'default' => null,
			'hosts'   => array()
		);

		// Order the sites so ones with explicit schemes take priority in the
		// map.
		$sites = Site::get();
		$sites->sort('Scheme', 'DESC');

		foreach($sites as $site) {
			if($site->IsDefault) {
				$this->map['default'] = $site->ID;
			}

			$hosts = array($site->Host);
			$hosts = array_merge($hosts, (array) $site->HostAliases->getValue());

			foreach($hosts as $host) {
				if($site->Scheme != 'https') {
					$this->map['hosts']["http://$host"] = $site->ID;
				}

				if($site->Scheme != 'http') {
					$this->map['hosts']["https://$host"] = $site->ID;
				}
			}
		}

		$this->cache->save($this->map, self::CACHE_KEY);
	}

	/**
	 * @return int
	 */
	public function getDefaultSiteId() {
		if(isset($this->map['default'])) return $this->map['default'];
	}

	/**
	 * @return Site
	 */
	public function getDefaultSite() {
		if(!$this->default && $id = $this->getDefaultSiteId()) {
			$this->default = Site::get()->byID($id);
		}

		return $this->default;
	}

	/**
	 * @return int
	 */
	public function getCurrentSiteId() {
		if(!$this->currentId && $this->map) {
			// Re-parse the protocol and host to ensure it's in a consistent
			// format.
			$host  = Director::protocolAndHost();
			$parts = parse_url($host);
			$host  = "{$parts['scheme']}://{$parts['host']}";

			if(isset($this->map['hosts'][$host])) {
				$this->currentId = $this->map['hosts'][$host];
			} else {
				$this->currentId = $this->getDefaultSiteId();
			}
		}

		return $this->currentId;
	}

	/**
	 * @return Site
	 */
	public function getCurrentSite() {
		if(!$this->current && $id = $this->getCurrentSiteId()) {
			$this->current = Site::get()->byID($id);
		}

		return $this->current;
	}

	/**
	 * @return Folder
	 */
	public function getAssetsFolder(){
		if(!$this->assetsFolder){
			$currentSite 		= $this->getCurrentSite();
			$siteFolderName 	= $currentSite->Host ? $currentSite->Host : "site-$currentSite->ID";
			$this->assetsFolder	= Folder::find_or_make($siteFolderName);	
		}

		return $this->assetsFolder;
	}

}
