<?php
/**
 * An extension to the default page add interface which doesn't allow pages to
 * be created on the root.
 *
 * @package silverstripe-multisites
 */
class MultisitesCMSPageAddController extends CMSPageAddController
{

    private static $allowed_actions = array(
        'AddForm'
    );

    public static $url_priority = 43;

    public function AddForm()
    {
        $form   = parent::AddForm();
        $fields = $form->Fields();

        $fields->push(new HiddenField('Parent', null, true));

        // Enforce a parent mode of "child" to correctly read the "allowed children".

        $fields->dataFieldByName('ParentModeField')->setValue('child');
        $fields->insertAfter($parent = new TreeDropdownField(
            'ParentID', '', 'SiteTree', 'ID', 'TreeTitle'
        ), 'ParentModeField');

        $parentID = $this->request->getVar('ParentID');
        $parentID = $parentID ? $parentID : Multisites::inst()->getCurrentSiteId();

        $parent->setForm($form);
        $parent->setValue((int)$parentID);

        $form->setValidator(new RequiredFields('ParentID'));
        return $form;
    }
}
