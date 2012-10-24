<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesFileFieldExtension extends Extension {

	/**
	 * prepends an assets/currentsite folder to the upload folder name.
	 **/
	public function useMultisitesFolder(){
		$multisiteFolder = Multisites::inst()->getAssetsFolder();
		if($multisiteFolder){
			$this->owner->setFolderName($multisiteFolder->Name . '/' . $this->owner->getFolderName());
		}

		return $this->owner;
	}

}
