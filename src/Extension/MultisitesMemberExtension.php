<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\Control\Session;
use SilverStripe\ORM\DataExtension;
class MultisitesMemberExtension extends DataExtension {
	
	public function memberLoggedIn(){
		Session::clear('Multisites_ActiveSite');
		Session::clear('MultisitesModelAdmin_SiteID'); // legacy
	}
}
