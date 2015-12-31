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
class RobotsTxtController extends Controller
{

    public static $allowed_actions = array(
        'robots.txt' => 'index'
    );

    public function index()
    {
        $site    = Multisites::inst()->getCurrentSiteId();

        if (!$site) {
            return $this->httpError(404);
        }

        $page = Site::get()->filter(array(
            'ID'   => $site
        ));

        $page = $page->first();

        if (!$page) {
            return $this->httpError(404);
        }

        /*
         * Trim the RobotsTxt field because it may be an empty string.
         * and since SilverStripe doesn't ship with a default robots.txt
         * file, we'll want to return a 404 if there isn't any text for
         * the site's robots.txt file.
         */
        $text = trim($page->RobotsTxt);

        if (empty($text)) {
            return $this->httpError(404);
        }

        $this->getResponse()->addHeader('Content-Type', 'text/plain; charset="utf-8"');
        return $text;
    }
}
