<?php

/**
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MultisitesControllerExtension extends Extension {

	/**
	 * Sets the theme to the current site theme
	 **/
	public function onAfterInit() {
		if ($this->owner instanceof DatabaseAdmin) {
			//
			// 2016-12-16 -	This is disabled in sitetree.yml to stop users placing
			//				pages above a Site. However, during dev/build we don't
			//				want pages validated so they can be placed top-level, and
			//				then be moved underneath Site during it's
			//				requireDefaultRecords() call.
			//
			SiteTree::config()->can_be_root = true;
			return;
		}

		if ($this->owner instanceof DevelopmentAdmin ||
			$this->owner instanceof DevBuildController ||
			$this->owner instanceof DatabaseAdmin) {
			return;
		}

		$site = Multisites::inst()->getCurrentSite();
		if (!$site) {
			return;
		}

		$theme = $site->getSiteTheme();
		if ($theme) {
			SSViewer::set_theme($theme);
		}

		// Update default uploads folder to site
		$folder = $site->Folder();
		if ($folder->exists()) {
			$assetPos = strpos($folder->getRelativePath(), ASSETS_DIR) + strlen(ASSETS_DIR);
			$siteAssetDir = trim(substr($folder->getRelativePath(), $assetPos), '/');
			Config::inst()->update('Upload', 'uploads_folder', $siteAssetDir);
		}
	}
	
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
