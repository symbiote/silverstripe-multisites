<?php

namespace Symbiote\Multisites;

use Symbiote\Multisites\Model\Site;

use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FileField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;

use Psr\SimpleCache\CacheInterface;

/**
 * Contains various multisites utility functions, should be accessed via the
 * {@link inst} method in most cases.
 *
 * @package silverstripe-multisites
 */
class Multisites
{
    const CACHE_KEY = 'multisites_map';

    private static $inst;

    /**
     * @var Array - A list of identifiers that can be assigned to a Site (in CMS => Site)
     * for a developer to identify a Site instance in their code.
     */
    private static $developer_identifiers;

    /**
     * @var Array - A list of features to be used on a given Site, identified by developer_identifiers
     */
    private static $site_features;

    /**
     *
     * @var Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    /**
     *
     * @var array
     */
    protected $map;

    /**
     *
     * @var mixed
     */
    protected $default;

    /**
     *
     * @var type Site
     */
    protected $current;

    /**
     * @return Multisites
     */
    public static function inst()
    {
        if (!self::$inst) {
            self::$inst = new self();
            self::$inst->init();
        }

        return self::$inst;
    }

    public function __construct()
    {
        $this->cache =  Injector::inst()->get(CacheInterface::class . '.multisites');
    }

    /**
     * Attempts to load the hostname map from the cache, and rebuilds it if
     * it cannot be loaded.
     */
    public function init()
    {
        $valid = null;

        if ($this->cache->has(self::CACHE_KEY)) {
            $cached = $this->cache->get(self::CACHE_KEY);
            $valid  = $cached && isset($cached['hosts']) && count($cached['hosts']);
        }


        if ($valid) {
            $this->map = $cached;
        } else {
            $this->build();
        }
    }

    /**
     * Builds a map of hostnames to sites, and writes it to the cache.
     */
    public function build()
    {
        $this->map = array(
            'default' => null,
            'hosts' => array()
        );

        if (!DB::is_active() ||
            !DB::get_schema()->hasTable(Site::config()->table_name)) {
            return;
        }

        $sites     = Site::get();

        /**
         * 	After duplicating a site, the duplicate contains the same host and causes a 404 during resolution.
         * 	IMPORTANT, this is required to prevent the site from going down.
         * 	---
         */
        $sites = $sites->sort(array(
            'ID' => 'DESC'
        ));

        // ---

        foreach ($sites as $site) {
            if ($site->IsDefault) {
                $this->map['default'] = $site->ID;
            }

            $hosts = array($site->Host);
            $hosts = array_merge($hosts, (array) $site->HostAliases->getValue());

            foreach ($hosts as $host) {
                if (!$host) {
                    continue;
                }
                if ($site->Scheme != 'https') {
                    $this->map['hosts']["http://$host"] = $site->ID;
                }

                if ($site->Scheme != 'http') {
                    $this->map['hosts']["https://$host"] = $site->ID;
                }
            }
        }

        $this->cache->set(self::CACHE_KEY, $this->map);
    }

    /**
     * @return int
     */
    public function getDefaultSiteId()
    {
        if (isset($this->map['default'])) return $this->map['default'];
    }

    /**
     * @return Site
     */
    public function getDefaultSite()
    {
        if (!$this->default && $id = $this->getDefaultSiteId()) {
            $this->default = Site::get()->byID($id);
        }

        return $this->default;
    }

    /**
     * @return int
     */
    public function getCurrentSiteId()
    {

        // Re-parse the protocol and host to ensure it's in a consistent
        // format.
        $host = Director::protocolAndHost();

        $parts = parse_url($host);
        $host  = "{$parts['scheme']}://{$parts['host']}";

        if ($this->map) {
            if (isset($this->map['hosts'][$host])) {
                return $this->map['hosts'][$host];
            } else {
                // see if we're using sub URLs
                $base = Director::baseURL();
                $host = rtrim($host.$base, '/');
                if (isset($this->map['hosts'][$host])) {
                    return $this->map['hosts'][$host];
                }
            }
        }

        return $this->getDefaultSiteId();
    }

    /**
     * @return Site
     */
    public function getCurrentSite()
    {
        if (!$this->current && $id = $this->getCurrentSiteId()) {
            $this->current = Site::get()->byID($id);
        }

        return $this->current;
    }

    /**
     * Reset the currently viewed site
     *
     * Useful for code that under the covers swaps the host that we're
     * looking at, in particular any static publisher functionality
     */
    public function resetCurrentSite()
    {
        $this->current = null;
    }

    /**
     * Get's the site related to what is currently being edited in the cms
     * If a page or site is being edited, it will look up the site of the sitetree being edited,
     * If a MultisitesAware object is being managed in ModelAdmin, ModelAdmin will have set a Session variable MultisitesModelAdmin_SiteID
     * @return Site
     */
    public function getActiveSite()
    {
        $controller = Controller::curr();
        if ($controller instanceof CMSPageEditController) {

            // requests to admin/pages/edit/EditorToolbar/viewfile?ID=XX
            // are not reliable because $controller->currentPage()
            // will return an incorrect page based on the ID $_GET parameter
            if ($controller->getRequest()->param('ID') != 'viewfile') {
                $page = $controller->currentPage();

                if (!$page) {
                    // fixes fatal error when duplicating page
                    // TODO find the root of the problem...
                    return Site::get()->first();
                }

                if ($page instanceof Site) {
                    $this->setActiveSite($page);
                    return $page;
                }

                $site = $page->Site();

                if ($site->ID) {
                    $this->setActiveSite($site);
                    return $site;
                }
            }
        } else if (is_subclass_of($controller, ModelAdmin::class)) {
            // if we are in a model admin that isn't using the global active_site_session,
            // return it's ActiveSite. This is important for cases where a multisite aware
            // data object is being saved for the first time in a model admin, we need to
            // know what site to save it to
            if (!$controller->config()->use_active_site_session) {
                return $controller->getActiveSite();
            }
        }

        if ($id = $controller->getRequest()->getSession()->get('Multisites_ActiveSite')) {
            return Site::get()->byID($id);
        }

        // if($id = Session::get('MultisitesModelAdmin_SiteID')) { // legacy
        // 	return Site::get()->byID($id);
        // }

        return $this->getCurrentSite();
    }

    public function setActiveSite($site)
    {
        $id = is_numeric($site) ? $site : $site->ID;
        $this->getSession()->set('Multisites_ActiveSite', $id);
        $this->getSession()->set('MultisitesModelAdmin_SiteID', $id); // legacy
    }

    /**
     * Checks to see if we should be using a subfolder in assets for each site.
     * @return Boolean
     * */
    public function assetsSubfolderPerSite()
    {
        return FileField::has_extension('MultisitesFileFieldExtension');
    }

    /**
     * Finds sites that the given member is a "Manager" of
     * A manager is currently defined by a Member who has edit access to a Site Object
     * @var Member $member
     * @return array - Site IDs
     * */
    public function sitesManagedByMember($member = null)
    {
        $member = $member ?: Member::currentUser();
        if (!$member) return array();

        $sites = Site::get();

        if (Permission::check('ADMIN')) {
            return $sites->column('ID');
        }

        $memberGroups = $member->Groups()->column('ID');
        $sites        = $sites->filter("EditorGroups.ID:ExactMatch", $memberGroups);

        return $sites->column('ID');
    }

    /**
     *
     * @return HTTPRequest
     */
    protected function getSession() {
        if (!Injector::inst()->has(HTTPRequest::class)) {
            return null;
        }

        $request = Injector::inst()->get(HTTPRequest::class);
        /* @var HTTPRequest $request */

        // Skip if the session hasn't been started
        return $request->getSession();
    }
}
