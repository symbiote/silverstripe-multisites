<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesInitTask extends BuildTask {

	protected $title = 'Multisites Initialisation Task';

	protected $description = 'Creates a default site and places all pages beneath it.';

	public function run($request) {
		if(Site::get()->count()) {
			echo "A site has already been created.\n";
			return;
		}

		$site = new Site();
		$site->Title = _t('Multisites.DEFAULTSITE', 'Default Site');
		$site->IsDefault = true;
		$site->write();

		$pages = SiteTree::get()->exclude('ID', $site->ID)->filter('ParentID', 0);
		$count = count($pages);

		foreach($pages as $page) {
			$page->ParentID = $site->ID;
			$page->write();
			$page->doPublish();
		}

		echo "Created a default site and placed $count pages under it.";
	}

}
