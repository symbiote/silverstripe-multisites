<?php
/**
 * @package silverstripe-multisites
 */
class MultisitesCMSMainExtension extends LeftAndMainExtension {

	public static $allowed_actions = array(
		'AddSiteForm'
	);

	public function getCMSTreeTitle() {
		return _t('Multisites.SITES', 'Sites');
	}

	public function AddSiteForm() {
		return new Form(
			$this->owner,
			'AddSiteForm',
			new FieldList(),
			new FieldList(
				FormAction::create('doAddSite', _t('Multisites.ADDSITE', 'Add Site'))
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon', 'add')
			)
		);
	}

	public function doAddSite() {
		$site = $this->owner->getNewItem('new-Site-0', false);
		$site->write();

		return $this->owner->redirect(
			singleton('CMSPageEditController')->Link("show/$site->ID")
		);
	}

	public function updateSearchForm(Form $form) {
		$cms = $this->owner;
		$req = $cms->getRequest();

		$sites =  Site::get()->sort(array(
			'IsDefault' => 'DESC',
			'Title'     => 'ASC'
		));

		$site = new DropdownField(
			'q[SiteID]',
			_t('Multisites.SITE', 'Site'),
			$sites->map(),
			isset($req['q']['SiteID']) ? $req['q']['SiteID'] : null
		);
		$site->setEmptyString(_t('Multisites.ALLSITES', 'All sites'));

		$form->Fields()->insertAfter($site, 'q[Term]');
	}

}
