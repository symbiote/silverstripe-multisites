<?php
/**
 * An extension to the default page add interface which doesn't allow pages to
 * be created on the root.
 *
 * @package silverstripe-multisites
 */
class MultisitesCMSPageAddController extends CMSPageAddController {

	public static $url_priority = 43;

	public function AddForm() {
		$form   = parent::AddForm();
		$fields = $form->Fields();

		$fields->push(new HiddenField('Parent', null, true));
		$fields->replaceField('ParentModeField', $parent = new TreeDropdownField(
			'ParentID', '', 'SiteTree', 'ID', 'TreeTitle'
		));

		$parent->setForm($form);
		$parent->setShowSearch(true);
		$parent->setValue((int) $this->request->getVar('ParentID'));

		$form->setValidator(new RequiredFields('ParentID'));
		return $form;
	}

}
