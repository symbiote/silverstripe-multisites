<?php

/**
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class MultisitesControllerExtension extends Extension {

	/**
	 * Sets the theme to the current site theme
	 **/
	public function onAfterInit() {
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
		$assetDir = Config::inst()->get('Upload', 'uploads_folder');
		$folder = $site->Folder();
		if ($folder->exists()) {
			$siteAssetDir = ltrim($folder->getRelativePath(), ASSETS_DIR.'/');
			$siteAssetDir = rtrim($siteAssetDir, '/');
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
