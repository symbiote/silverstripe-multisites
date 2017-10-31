<?php
namespace Symbiote\Multisites\Admin;

use Symbiote\Multisites\Multisites;

use SilverStripe\Forms\HiddenField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\CMS\Controllers\CMSPageAddController;


/**
 * An extension to the default page add interface which doesn't allow pages to
 * be created on the root.
 *
 * Can't do a direct extension because that doesn't allow us to control the form object itself. 
 *
 * @package silverstripe-multisites
 */
class MultisitesCMSPageAddController extends CMSPageAddController {

    private static $menu_title = 'Add page';


	private static $allowed_actions = array(
		'AddForm'
	);

	private static $url_priority = 43;

	public function AddForm() {
		$form   = parent::AddForm();
		$fields = $form->Fields();

		$fields->push(new HiddenField('Parent', null, true));

		// Enforce a parent mode of "child" to correctly read the "allowed children".
        $parentMode = $fields->dataFieldByName('ParentModeField');

		$parentMode->removeByName('Top level');
        $parentMode->setValue('child');
        
		$fields->insertAfter($parent = new TreeDropdownField(
			'ParentID', '', SiteTree::class, 'ID', 'TreeTitle'
		), 'ParentModeField');

		$parentID = $this->request->getVar('ParentID');
		$parentID = $parentID ? $parentID : Multisites::inst()->getCurrentSiteId();

		$parent->setForm($form);
		$parent->setValue((int)$parentID);

		$form->setValidator(new RequiredFields('ParentID'));
		return $form;
	}

}
