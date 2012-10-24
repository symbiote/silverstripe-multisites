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

}
