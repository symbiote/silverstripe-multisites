<?php
/**
 * Controller for displaying the robots.txt file for a multisites enabled site.
 *
 * <code>
 * http://site.com/robots.txt
 * </code>
 *
 * @package silverstripe-multisites
 */
class RobotsTxtController extends Controller {

    public static $allowed_actions = array(
        'robots.txt' => 'index'
    );

    public function index() {

        $site    = Multisites::inst()->getCurrentSiteId();

        if(!$site) {
            return $this->getNotFoundResponse();
        }

        $page = Site::get()->filter(array(
            'ID'   => $site
        ));

        $page = $page->first();

        if(!$page) {
            return $this->getNotFoundResponse();
        }

        /*
         * Trim the RobotsTxt field because it may be an empty string.
         * and since SilverStripe doesn't ship with a default robots.txt
         * file, we'll want to return a 404 if there isn't any text for
         * the site's robots.txt file.
         */
        $text = trim($page->RobotsTxt);

        if(empty($text)) {
            return $this->getNotFoundResponse();
        }

        $this->getResponse()->addHeader('Content-Type', 'text/plain; charset="utf-8"');
        return $text;
    }

    /**
     * Finds the current site's ErrorPage of ErrorCode 404, to redirect the user to
     * if the requested page is not found.
     **/
    protected function getNotFoundResponse($siteId = null) {
        $page = ErrorPage::get()->filter(array(
            'ErrorCode' => 404,
            'SiteID'    => $siteId ?: Multisites::inst()->getDefaultSiteId()
        ));

        if($page = $page->first()) {
            $controller = ModelAsController::controller_for($page);
            $request    = new SS_HTTPRequest('GET', '');

            return $controller->handleRequest($request, $this->model);
        } else {
            return new SS_HTTPResponse(null, 404);
        }
    }
}