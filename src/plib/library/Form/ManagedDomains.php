<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_Form_ManagedDomains extends pm_Form_Simple
{
    public function init()
    {
        parent::init();

        $this->addElement('description', 'description', [
            'description' => $this->lmsg('createManagedDomainHint'),
            'escape' => false,
        ]);

        $this->addElement('text', 'managedDomain', [
            'label' => pm_Locale::lmsg('managedDomainLabel'),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => [
                ['NotEmpty', true],
            ],
        ]);

        $this->addElement('radio', 'mode', [
            'label' => pm_Locale::lmsg('managedDomainModeLabel'),
            'required' => true,
            'multiOptions' => [
                Modules_Route53_Settings::MANAGED_DOMAIN_WITHOUT_NS
                => $this->lmsg('managedDomainsMode' . Modules_Route53_Settings::MANAGED_DOMAIN_WITHOUT_NS),
            ],
            'validators' => [
                ['NotEmpty', true],
            ],
        ]);

        $this->addControlButtons([
            'cancelLink' => pm_Context::getBaseUrl(),
        ]);
    }

    public function process()
    {
        Modules_Route53_Settings::addManagedDomain($this->getValue('managedDomain'), $this->getValue('mode'));
    }
}
