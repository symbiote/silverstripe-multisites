<?php
class MultisitesMemberExtension extends DataExtension {
	
	public function memberLoggedIn(){
		Session::clear('MultisitesModelAdmin_SiteID');
	}
}
