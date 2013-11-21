<?php
/**
 * An extension to the default page add interface which doesn't allow pages to
 * be created on the root.
 *
 * @package silverstripe-multisites
 */
class MultisitesCMSPageAddController extends CMSPageAddController {

	private static $allowed_actions = array(
		'AddForm'
	);

	public static $url_priority = 43;

	public function AddForm() {
		$form   = parent::AddForm();
		$fields = $form->Fields();

		$fields->push(new HiddenField('Parent', null, true));
		$fields->replaceField('ParentModeField', $parent = new TreeDropdownField(
			'ParentID', '', 'SiteTree', 'ID', 'TreeTitle'
		));

		$parentID = $this->request->getVar('ParentID');
		$parentID = $parentID ? $parentID : Multisites::inst()->getCurrentSiteId();

		$parent->setForm($form);
		$parent->setValue((int)$parentID);

		$form->setValidator(new RequiredFields('ParentID'));
		return $form;
	}

}
