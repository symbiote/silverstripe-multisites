<?php

namespace Symbiote\Multisites\Job;

use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DB;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;

/**
 * 	This task is designed to smooth out data integrity edge cases when duplicating a site object and children.
 * 	PLEASE NOTE, it's also handy to just clean up a site object and children (in a non-destructive manner).
 * 	@author Nathan Glasl <nathan@symbiote.com.au>
 */
class TidySiteTask extends BuildTask
{
    private static $segment = 'TidySiteTask';
    protected $description = "This task is designed to smooth out data integrity edge cases when duplicating a site object and children. PLEASE NOTE, it's also handy to just clean up a site object and children (in a non-destructive manner).";
    protected $siteID;
    protected $count       = 0;
    // This is so something (events for example) can be purged.

    protected $purge;
    protected $purged = 0;

    public function run($request)
    {

        $this->siteID = $request->getVar('siteID');
        $purge        = $request->getVar('purge');
        if (class_exists($purge)) {
            $this->purge = $purge;
        }
        if ($this->siteID) {

            // To begin, clean up the draft pages.

            Versioned::reading_stage('Stage');
            $site = Site::get()->byID($this->siteID);
            if ($site) {
                $this->recursiveTidy($site->AllChildren());
            }

            // Then, clean up the published pages (separately to prevent issues).

            Versioned::reading_stage('Live');
            $site = Site::get()->byID($this->siteID);
            if ($site) {
                $this->recursiveTidy($site->AllChildren());
            }

            // Success!

            DB::alteration_message('Done!');
            DB::alteration_message("`<strong>{$this->count}</strong>` Page/s Fixed.");
            DB::alteration_message("`<strong>{$this->purged}</strong>` Purged.");
        } else {
            DB::alteration_message('Please supply a `<strong>siteID</strong>`.');
        }
    }

    /**
     * 	This recursively goes through children to ensure everything is tidy.
     */
    public function recursiveTidy($children)
    {

        foreach ($children as $page) {
            $write = false;

            // This is so something (events for example) can be purged.

            if ($this->purge && is_a($page, $this->purge, true)) {
                $page->delete();
                $this->purged++;
                continue;
            }

            // The most common issue is that the site ID doesn't match.

            if ($page->SiteID != $this->siteID) {

                // Update it to match.

                $page->SiteID = $this->siteID;
                $write        = true;
            }

            // The next issue is that duplicated URL segments have numbers appended (`URL-segment-2` for example).

            if (preg_match('%-[0-9]$%', $page->URLSegment)) {

                // When a page title ends with a number, the URL should match this.

                $last = substr($page->Title, -1);
                if (is_numeric($last)) {

                    // Determine whether this actually needs fixing.

                    if ($last !== substr($page->URLSegment, -1)) {

                        // Update it to match.

                        $page->URLSegment = $page->Title;
                        $write            = true;
                    }
                }

                // The number appended shouldn't exist.
                else {

                    // Determine whether this is considered unique (otherwise it can't be updated).

                    $to = substr($page->URLSegment, 0, -2);
                    if (!SiteTree::get()->filter(array(
                            'ParentID' => $page->ParentID,
                            'URLSegment' => $to
                        ))->exists()) {

                        // This isn't unique, so update it.

                        $page->URLSegment = $to;
                        $write            = true;
                    }
                }
            }

            // Determine whether this page needs a write.

            if ($write) {
                $page->write();
                $this->count++;
            }

            // Where necessary, continue further down.

            $this->recursiveTidy($page->AllChildren());
        }
    }
}