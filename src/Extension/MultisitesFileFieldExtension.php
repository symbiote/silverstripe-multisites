<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\Core\Extension;

/**
 * @package silverstripe-multisites
 */
class MultisitesFileFieldExtension extends Extension
{

    /**
     * prepends an assets/currentsite folder to the upload folder name.
     * */
    public function useMultisitesFolder()
    {
        $site = Multisites::inst()->getActiveSite();
        if (!$site) {
            return $this->owner;
        }
        $multisiteFolder = $site->Folder();

        if (!$multisiteFolder->exists()) {
            $site->createAssetsSubfolder(true);
            $multisiteFolder = $site->Folder();
        }

        $this->owner->setFolderName($multisiteFolder->Name.'/'.$this->owner->getFolderName());

        return $this->owner;
    }
}