<?php
/**
 * Contains various multisites utility functions, should be accessed via the
 * {@link inst} method in most cases.
 *
 * @package silverstripe-multisites
 */
class Multisites {

	const CACHE_KEY = 'multisites_map';

	// Store the singleton instance of this class
	private static $inst; 

	/**
	 * @var Array - A list of identifiers that can be assigned to a Site (in CMS => Site)
	 * for a developer to identify a Site instance in their code.
	 */
	public static $developer_identifiers;

	protected $cache;
	
	/**
	 * @var array $map An map of site to domain relationships.
	 * 
	 * Structure of this array varies depending on whether or not the Translatable module is applied to the SiteTree.
	 * 
	 * Without Translatable
	 * array(
	 *		'default' => 1, // SiteID of the default site
	 *		'hosts' => array(
	 *			'http://www.host.com' => 1, // SiteID of the Site which should be served for this host.
	 *			'https://www.host.com' => 1,
	 *			'http://www.host2.com' => 3
	 *		)
	 * );
	 *			
	 * With Translatable
	 * array(
	 *		'default' => array(
	 *			'en_AU' => 1, // SiteID of the default site for this locale
	 *			'en_NZ' => 4
	 *		),
	 *		'hosts' => array(
	 *			'http://www.host.com' => array(
	 *				'en_AU' => 1, // SiteID of the Site which should be served for this host/locale combination.
	 *				'en_NZ' => 4
	 *			),
	 *			'http://www.host2.com' => array(
	 *				'en_AU' => 2, 
	 *				'en_NZ' => 5
	 *			)
	 *		)
	 * );
	 * 
	 */
	protected $map;

	protected $default;
	protected $currentId;
	protected $current;
	protected $assetsFolder;
	

	/**
	 * Get the Multisites singleton.
	 * 
	 * @return Multisites
	 */
	public static function inst() {
		if(!self::$inst) {
			self::$inst = new self();
			self::$inst->init();
		}

		return self::$inst;
	}

	/**
	 * Singleton pattern = private constructor.
	 */
	private function __construct() {
		$this->cache = SS_Cache::factory('Multisites', 'Core', array(
			'automatic_serialization' => true
		));
	}
	
	/**
	 * Constructor logic.
	 * 
	 * Attempts to load the hostname map from the cache, and rebuilds it if
	 * it cannot be loaded.
	 */
	private function init() {
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
		
		$this->map = array();
		
		if(class_exists('Translatable')) Translatable::disable_locale_filter();

		// Order the sites so ones with explicit schemes take priority in the map.
		$sites = Site::get();
		$sites->sort('Scheme', 'DESC');
		
		// Is the site tree translatable?
		$translatable = singleton('SiteTree')->hasExtension('Translatable');
		
		// Setup map structure
		$this->map['default'] = ($translatable) ? array() : null;
		$this->map['hosts'] = array();
		
		// Add each site to the map
		foreach($sites as $site) {
			
			if($site->IsDefault) {
				if ($translatable) {
					$this->map['default'][$site->Locale] = $site->ID;
				} else {
					$this->map['default'] = $site->ID;
				}
			}

			// Compile all possible hosts for this site
			$hosts = array($site->Host);
			$hosts = array_merge($hosts, (array) $site->HostAliases->getValue());

			foreach($hosts as $host) {
				
				// Compile the schemes for this host
				$schemes = array();
				if($site->Scheme != 'https') $schemes[] = 'http';
				if($site->Scheme != 'http') $schemes[] = 'https';
				
				foreach ($schemes as $scheme) {
					
					// Setup structure of hosts map
					if ( !isset($this->map['hosts']["$scheme://$host"]) ) {
						$this->map['hosts']["$scheme://$host"] = ($translatable) ? array() : null;
					}
					
					if ($translatable) {
						$this->map['hosts']["$scheme://$host"][$site->Locale] = $site->ID;
					} else {
						$this->map['hosts']["$scheme://$host"] = $site->ID;
					}
				}
			}
		}
		if(class_exists('Translatable')) Translatable::enable_locale_filter();
		
		$this->cache->save($this->map, self::CACHE_KEY);
	}

	/**
	 * Get the default site ID
	 * 
	 * @return int|null
	 */
	public function getDefaultSiteId() {
		if (singleton('SiteTree')->hasExtension('Translatable')) {
			$locale = Translatable::get_current_locale();
			return (isset($this->map['default']) && isset($this->map['default'][$locale])) ? $this->map['default'][$locale] : null;
		} else {
			return (isset($this->map['default'])) ? $this->map['default'] : null;
		}
	}

	/**
	 * Get the default site object for this domain (and locale).
	 * 
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
		
		// If there isn't already on, set it if we can.
		if(!$this->currentId && $this->map) {
			
			// Set the fallback to the default site ID
			$this->currentId = $this->getDefaultSiteId();
			
			// Re-parse the protocol and host to ensure it's in a consistent format.
			$host  = Director::protocolAndHost();
			$parts = parse_url($host);
			$host  = rtrim("{$parts['scheme']}://{$parts['host']}" . Director::baseURL(),'/');

			// If there's a default in the map for this domain, use it.
			if(isset($this->map['hosts'][$host])) {
				$this->currentId = ( singleton('SiteTree')->hasExtension('Translatable') ) 
						? $this->map['hosts'][$host][Translatable::get_current_locale()] 
						: $this->map['hosts'][$host];
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
			$siteFolderName 	= $currentSite->Host ? str_replace('/', '-', $currentSite->Host) : "site-$currentSite->ID";
			$this->assetsFolder	= Folder::find_or_make($siteFolderName);	
		}

		return $this->assetsFolder;
	}
	
	/**
	 * Reset cached current site variables. This is required if the current site object changes
	 * mid request like it can in {@link MultisitesFrontController}.
	 */
	public function reset() {
		$this->currentId = null;
		$this->current = null;
		$this->assetsFolder = null;
	}


}
