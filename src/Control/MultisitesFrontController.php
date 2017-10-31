<?php

namespace Symbiote\Multisites\Control;

use Symbiote\Multisites\Multisites;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\CMS\Controllers\OldPageRedirector;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\ModelAsController;
/**
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController {


	/**
	 * Overrides ModelAsController->getNestedController to find the nested controller
	 * on a per-site basis
	 **/
	public function getNestedController() {
		$request = $this->request;
		$segment = $request->param('URLSegment');
		$site    = Multisites::inst()->getCurrentSiteId();

		if(!$site) {
			return $this->httpError(404);
		}

		$page = SiteTree::get()->filter(array(
			'ParentID'   => $site,
			'URLSegment' => rawurlencode($segment)
		));
		$page = $page->first();

		if(!$page) {
			// Check to see if linkmapping module is installed and if so, if there a map for this request.
			if(class_exists('LinkMapping')){
				$queryString = '?';
				if ($request->requestVars()){
					foreach($request->requestVars() as  $key => $value) {
						if($key !='url'){$queryString .=$key.'='.$value.'&';}
					}
					$queryString =  rtrim($queryString,'&');
				}
				$link = ($queryString != '?' ? $request->getURL().$queryString : $request->getURL());
				$link = trim(Director::makeRelative($link));

				$map  = LinkMapping::get()->filter('MappedLink', $link)->first();

				if ($map) {
					$this->response = new HTTPResponse();
					$this->response->redirect($map->getLink(), 301);
					return $this->response;
				}	
			}
	
			// use OldPageRedirector if it exists, to find old page
			if(class_exists(OldPageRedirector::class)){
				if($redirect = OldPageRedirector::find_old_page(array($segment), Multisites::inst()->getCurrentSite())){
					$redirect = SiteTree::get_by_link($redirect);
				}
			}else{
				$redirect = self::find_old_page($segment, $site);			
			}			

			if($redirect) {
				$getVars = $request->getVars();
				//remove the url var as it confuses the routing
				unset($getVars['url']);
				
				$url = Controller::join_links(
						$redirect->Link(
							Controller::join_links(
								$request->param('Action'),
								$request->param('ID'),
								$request->param('OtherID')
							)
						)
					);
				
				if(!empty($getVars)){
					$url .= '?' . http_build_query($getVars);
				}
				
				$this->response->redirect($url, 301);

				return $this->response;
			}

			return $this->httpError(404);
		}


		return self::controller_for($page, $request->param('Action'));
	}

}
