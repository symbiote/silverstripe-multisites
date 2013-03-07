<?php
/**
 * This file is used as the controller for requests to the front end of the website. To achieve
 * this the _config/routes.yml overrides the default routing provided by the CMS.
 * 
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController {

	/**
	 * This function is respoinsible for figuring out which site the user is accessing and
	 * ultimately which page the user is loading.
	 * 
	 * Return the instance of RequestHandler that should initially handle this request. This
	 * will be the page controller for the child of Site in the ancestors of the hierarchy 
	 * of the page ultimately being requested.
	 * 
	 * @return RequestHandler|SS_HTTPResponse
	 */
	public function getNestedController() {
		$request = $this->request;
		$segment = $request->param('URLSegment');
		$site    = Multisites::inst()->getCurrentSite(); // This will be the Site for this domain with the default locale
		
		// If the current site can't be identified there's no way to continue. Return a 404.
		if(!$site) {
			return $this->getNotFoundResponse();
		}

		// Get the top level page in this site by URLSegment
		if(class_exists('Translatable')) Translatable::disable_locale_filter();
		$page = SiteTree::get()
			->filter(array(
				'ParentID'   => $site->ID,
				'URLSegment' => rawurlencode($segment)
			))
			->first();
		if(class_exists('Translatable')) Translatable::enable_locale_filter();
		
		// If there's no page matching that description, maybe it's been moved
		if( !$page && $redirect = $this->checkHistory($segment, $site->ID, $request) ) {
			// The redirect itself is handled in checkHistory();
			return $this->response;
		}
		
		// If the page doesn't exist in this site maybe it's in a translated version of the site.
		// 
		// TODO: This is a big compromise and could do with a re-think. It could return ambiguious results.
		// For example, if there are multiple translated pages with the same URLSegment that are children of
		// a Site record, we have no way to know which one the user is trying to load. This won't happen if 
		// the default URLSegment provided by the Translatable module is kept, but there's no reason the user
		// has to keep it. The only time this is expected to be a problem is when the a particular URLSegment
		// translates to the same thing in two languages - not out of the question, but probably relatively
		// rare.
		// 
		// One sure way for developers to avoid this problem is to set the Locale in the request object. This
		// way, Translatable::get_current_locale() will return the correct locale for the request and 
		// Multisites->getCurrentSiteId will return the correct site for the locale.
		if ( !$page && $site->hasExtension('Translatable')) {
			foreach ($site->getTranslations() as $t) {
				
				Translatable::disable_locale_filter();
				$page = SiteTree::get()
					->filter(array(
						'ParentID'   => $t->ID,
						'URLSegment' => rawurlencode($segment)
					))
					->first();
				Translatable::enable_locale_filter();
				
				// Check the history
				if ( !$page && $redirect = $this->checkHistory($segment, $t->ID, $request) ) {
					return $this->response;
				}
				
				if ( $page ) {
					// The actual site object has changed so the cached variables in the Multisites singleton are no longer valid.
					Multisites::inst()->reset();
					Translatable::set_current_locale($page->Locale);
					break;
				}
			}
		}
		
		// If there's still no page, options are exhausted
		if ( !$page ) return $this->getNotFoundResponse($site->ID);

		return self::controller_for($page, $request->param('Action'));
	}
	
	/**
	 * Check if the page that can't be found has just been moved.
	 * 
	 * @param string $segment The page URLSegment
	 * @param int $parent The parent ID
	 * @return boolean|SS_HTTPResponse The response if there is a page to redirect to or false if no history was found. 
	 */
	private function checkHistory($segment, $parent, $request) {
		if($redirect = self::find_old_page($segment, $parent)) { 
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
		return false;
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

}
