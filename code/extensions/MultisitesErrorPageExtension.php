<?php
/**
 * Publishes separate static error pages for each site.
 *
 * @package silverstripe-multisites
 */
class MultisitesErrorPageExtension extends SiteTreeExtension
{

    public function alternateFilepathForErrorcode($code, $locale)
    {
        $path  = ErrorPage::get_static_filepath();
        $parts = array();

        if ($site = Multisites::inst()->getActiveSite()) {
            $parts[] = $site->Host;
        }

        $parts[] = $code;

        if ($locale && $this->owner->hasExtension('Translatable') && $locale != Translatable::default_locale()) {
            $parts[] = $locale;
        }

        return sprintf("%s/error-%s.html", $path, implode('-', $parts));
    }
}
