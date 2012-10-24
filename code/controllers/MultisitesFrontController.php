<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController {

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
			if($redirect = self::find_old_page($segment, $site)) {
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

				return $this->respones;
			}

			return $this->getNotFoundResponse($site);
		}

		if(class_exists('Translatable') && $page->Locale) {
			Translatable::set_current_locale($page->Locale);
		}

		return self::controller_for($page, $request->param('Action'));
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
