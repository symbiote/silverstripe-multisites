<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\Control\Session;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;

/**
 * Ensures a user who logs in has their active site reset
 */
class MultisitesMemberExtension extends DataExtension
{

    public function memberLoggedIn()
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $session = $request->getSession();
        if ($session) {
            $session->clear('Multisites_ActiveSite');
            $session->clear('MultisitesModelAdmin_SiteID'); // legacy
        }
    }
}