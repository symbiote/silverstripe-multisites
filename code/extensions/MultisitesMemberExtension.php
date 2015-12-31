<?php
class MultisitesMemberExtension extends DataExtension
{
    
    public function memberLoggedIn()
    {
        Session::clear('Multisites_ActiveSite');
        Session::clear('MultisitesModelAdmin_SiteID'); // legacy
    }
}
