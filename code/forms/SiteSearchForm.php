<?php
/**
 * Subclassed SearchForm to filter SiteTree results by the current site
 *
 * @package silverstripe-multisites
 */
class SiteSearchForm extends SearchForm
{
    
    /**
     * Restrict searches to the site 
     *
     * @var boolean
     */
    private static $restrict_files_by_site = true;
    
    public function getResults($pageLength = null, $data = null)
    {
        // legacy usage: $data was defaulting to $_REQUEST, parameter not passed in doc.silverstripe.org tutorials
        if (!isset($data) || !is_array($data)) {
            $data = $_REQUEST;
        }
        
        // set language (if present)
        if (class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
            $origLocale = Translatable::get_current_locale();
            Translatable::set_current_locale($data['locale']);
        }
    
        $keywords = $data['Search'];

        $andProcessor = create_function('$matches', '
	 		return " +" . $matches[2] . " +" . $matches[4] . " ";
	 	');
        $notProcessor = create_function('$matches', '
	 		return " -" . $matches[3];
	 	');

        $keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);
        
        $keywords = $this->addStarsToKeywords($keywords);

        if (!$pageLength) {
            $pageLength = $this->pageLength;
        }
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        
        $siteFilter = '';
        $fileFilter = "ID != 0";
        $site = Multisites::inst()->getCurrentSite();
        $siteFilter = 'SiteID = ' . $site->ID;
            
        if ($this->config()->restrict_files_by_site) {
            if ($site->FolderID) {
                $prefix = $site->Folder()->Filename;
                if (strlen($prefix)) {
                    $fileFilter .= ' AND "Filename" LIKE \'' . Convert::raw2sql($prefix).'%\'';
                }
            }
        }

        if (strpos($keywords, '"') !== false || strpos($keywords, '+') !== false || strpos($keywords, '-') !== false || strpos($keywords, '*') !== false) {
            $results = DB::getConn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength, "\"Relevance\" DESC", $siteFilter, true, $fileFilter);
        } else {
            $results = DB::getConn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength, '', $siteFilter, false, $fileFilter);
        }
        
        // filter by permission
        if ($results) {
            foreach ($results as $result) {
                if (!$result->canView()) {
                    $results->remove($result);
                }
            }
        }
        
        // reset locale
        if (class_exists('Translatable') && singleton('SiteTree')->hasExtension('Translatable') && isset($data['locale'])) {
            Translatable::set_current_locale($origLocale);
        }

        return $results;
    }
}
