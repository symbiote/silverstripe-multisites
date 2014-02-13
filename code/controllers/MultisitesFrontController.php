<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController {


	/**
	 * Overrides ModelAsController->getNestedController to find the nested controller
	 * on a per-site basis
	 **/
	public function getNestedController() {
		$request = $this->request;
		$segment = $request->param('URLSegment');
		$site    = Multisites::inst()->getCurrentSiteId();

		if(!$site) {
			return $this->getNotFoundResponse();
		}

		if(class_exists('Translatable')) Translatable::disable_locale_filter();

		$page = SiteTree::get()->filter(array(
			'ParentID'   => $site,
			'URLSegment' => rawurlencode($segment)
		));
		$page = $page->first();

		if(class_exists('Translatable')) Translatable::enable_locale_filter();

		if(!$page) {
			// Check to see if linkmapping module is installed and if so, if there a map for this request.
			if(class_exists('LinkMapping')){
				if ($request->requestVars()){
					$queryString = '?';
					foreach($request->requestVars() as  $key => $value) {
						if($key !='url'){$queryString .=$key.'='.$value.'&';}
					}
					$queryString =  rtrim($queryString,'&');
				}
				$link = ($queryString != '?' ? $request->getURL().$queryString : $request->getURL());
				$link = trim(Director::makeRelative($link));

				$map  = LinkMapping::get()->filter('MappedLink', $link)->first();

				if ($map) {
					$this->response = new SS_HTTPResponse();
					$this->response->redirect($map->getLink(), 301);
					return $this->response;
				}	
			}
	
			// use OldPageRedirector if it exists, to find old page
			if(class_exists('OldPageRedirector')){
				if($redirect = OldPageRedirector::find_old_page(array($segment), Multisites::inst()->getCurrentSite())){
					$redirect = SiteTree::get_by_link($url);
				}
			}else{
				$redirect = self::find_old_page($segment, $site);			
			}			

			if($redirect) {
				$this->response->redirect(
					Controller::join_links(
						$redirect->Link(
							Controller::join_links(
								$request->param('Action'),
								$request->param('ID'),
								$request->param('OtherID')
							)
						),
						http_build_query($request->getVars())
					),
					301
				);

				return $this->response;
			}

			return $this->getNotFoundResponse($site);
		}

		if(class_exists('Translatable') && $page->Locale) {
			Translatable::set_current_locale($page->Locale);
		}

		return self::controller_for($page, $request->param('Action'));
	}


	/**
	 * Finds the current site's ErrorPage of ErrorCode 404, to redirect the user to
	 * if the requested page is not found
	 **/
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

}
