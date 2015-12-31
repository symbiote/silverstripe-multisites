<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesFrontController extends ModelAsController
{


    /**
     * Overrides ModelAsController->getNestedController to find the nested controller
     * on a per-site basis
     **/
    public function getNestedController()
    {
        $request = $this->request;
        $segment = $request->param('URLSegment');
        $site    = Multisites::inst()->getCurrentSiteId();

        if (!$site) {
            return $this->httpError(404);
        }

        if (class_exists('Translatable')) {
            Translatable::disable_locale_filter();
        }

        $page = SiteTree::get()->filter(array(
            'ParentID'   => $site,
            'URLSegment' => rawurlencode($segment)
        ));
        $page = $page->first();

        if (class_exists('Translatable')) {
            Translatable::enable_locale_filter();
        }

        if (!$page) {
            // Check to see if linkmapping module is installed and if so, if there a map for this request.
            if (class_exists('LinkMapping')) {
                if ($request->requestVars()) {
                    $queryString = '?';
                    foreach ($request->requestVars() as  $key => $value) {
                        if ($key !='url') {
                            $queryString .=$key.'='.$value.'&';
                        }
                    }
                    $queryString =  rtrim($queryString, '&');
                }
                $link = ($queryString != '?' ? $request->getURL().$queryString : $request->getURL());
                $link = trim(Director::makeRelative($link));

                $map  = LinkMapping::get()->filter('MappedLink', $link)->first();

                if ($map) {
                    $this->response = new SS_HTTPResponse();
                    $this->response->redirect($map->getLink(), 301);
                    return $this->response;
                }
            }
    
            // use OldPageRedirector if it exists, to find old page
            if (class_exists('OldPageRedirector')) {
                if ($redirect = OldPageRedirector::find_old_page(array($segment), Multisites::inst()->getCurrentSite())) {
                    $redirect = SiteTree::get_by_link($redirect);
                }
            } else {
                $redirect = self::find_old_page($segment, $site);
            }

            if ($redirect) {
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
                
                if (!empty($getVars)) {
                    $url .= '?' . http_build_query($getVars);
                }
                
                $this->response->redirect($url, 301);

                return $this->response;
            }

            return $this->httpError(404);
        }

        if (class_exists('Translatable') && $page->Locale) {
            Translatable::set_current_locale($page->Locale);
        }

        return self::controller_for($page, $request->param('Action'));
    }
}
