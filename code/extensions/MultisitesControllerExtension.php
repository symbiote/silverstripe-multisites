<?php

/**
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MultisitesControllerExtension extends Extension {

	/**
	 *	Retrieve the correct error page for the current multisite instance.
	 *	@param integer
	 *	@param SS_HTTPRequest
	 *	@throws SS_HTTPResponse_Exception
	 */
	public function onBeforeHTTPError($code, $request) {

		$errorPage = ErrorPage::get()->filter(array(
			'ErrorCode' => $code,
			'SiteID' => Multisites::inst()->getCurrentSiteId()
		))->first();
		if($errorPage) {
			Requirements::clear();
			Requirements::clear_combined_files();
			$response = ModelAsController::controller_for($errorPage)->handleRequest($request, DataModel::inst());
			throw new SS_HTTPResponse_Exception($response, $code);
		}
	}

}
