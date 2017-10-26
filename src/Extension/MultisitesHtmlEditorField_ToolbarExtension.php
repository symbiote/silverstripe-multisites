<?php

namespace Symbiote\Multisites\Extension;

use SilverStripe\Core\Extension;
/**
 * @package silverstripe-multisites
 */
class MultisitesHtmlEditorField_ToolbarExtension extends Extension {

	/**
	 * prepends an assets/currentsite folder to the upload folder name.
	 **/
	public function updateMediaForm($form){
		$form->Fields()->dataFieldByName('AssetUploadField')->useMultisitesFolder();
	}

}
