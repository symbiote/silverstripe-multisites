<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesRootController extends RootURLController {

	public function handleRequest(SS_HTTPRequest $request, DataModel $model = null) {
		self::$is_at_root = true;

		$this->setDataModel($model);
		$this->pushCurrent();
		$this->init();

		if(!$site = Multisites::inst()->getCurrentSiteId()) {
			return $this->getNotFoundResponse();
		}

		$page = SiteTree::get()->filter(array(
			'ParentID'   => $site,
			'URLSegment' => 'home'
		));

		if(!$page = $page->first()) {
			return $this->getNotFoundResponse($site);
		}

		$request = new SS_HTTPRequest(
			$request->httpMethod(),
			$page->RelativeLink(),
			$request->getVars(),
			$request->postVars()
		);
		$request->match('$URLSegment//$Action', true);

		$front    = new MultisitesFrontController();
		$response = $front->handleRequest($request, $model);

		$this->popCurrent();
		return $response;
	}

	protected function getNotFoundResponse($siteId = null) {
		$page = ErrorPage::get()->filter(array(
			'ErrorCode' => 404,
			'SiteID'    => $siteId ?: Multisites::inst()->getDefaultSiteId()
		));

		if($page = $page->first()) {
			$controller = ModelAsController::controller_for($page);
			$request    = new SS_HTTPRequest('GET', '');

			return $controller->handleRequest($request, $this->model);
		} else {
			return new SS_HTTPResponse(null, 404);
		}
	}
	
	/**
	 * The the (relative) homepage link.
	 * TODO: Should this deal with HomepageForDomain and Translatable the same way the core equivalent does?
	 * 
	 * @return string
	 */
	public static function get_homepage_link() {
		return Config::inst()->get(get_called_class(), 'default_homepage_link');
	}
	
	/**
	 * Returns TRUE if a request to a certain page should be redirected to the site root (i.e. if the page acts as the
	 * home page).
	 * 
	 * TODO: This function wouldn't be required if core called static::get_homepage_link() rather than self::get_homepage_link(). Raise a bug?
	 *
	 * @param SiteTree $page
	 * @return bool
	 */
	public static function should_be_on_root(SiteTree $page) {
		if(!self::$is_at_root && self::get_homepage_link() == trim($page->RelativeLink(true), '/')) {
			return !(
				class_exists('Translatable') && $page->hasExtension('Translatable') && $page->Locale && $page->Locale != Translatable::default_locale()
			);
		}
		return false;
	}

}
