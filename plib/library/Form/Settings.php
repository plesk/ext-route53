<?php
// Copyright 1999-2015. Parallels IP Holdings GmbH.
class Modules_Route53_Form_Settings extends pm_Form_Simple
{
    public function init()
    {
        parent::init();

        $this->addElement('text', 'key', array(
            'label' => $this->lmsg('keyLabel'),
            'value' => pm_Settings::get('key'),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $this->addElement('text', 'secret', array(
            'label' => $this->lmsg('secretLabel'),
            'value' => pm_Settings::get('secret'),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $this->addElement('checkbox', 'enabled', array(
            'label' => $this->lmsg('enabledLabel'),
            'value' => pm_Settings::get('enabled'),
        ));

        $this->addControlButtons(array(
            'cancelLink' => pm_Context::getModulesListUrl(),
        ));
    }

    public function isValid($data)
    {
        if ($data['enabled']) {
            try {
                Modules_Route53_Client::factory(['key' => $data['key'], 'secret' => $data['secret']])
                    ->checkCredentials();
            } catch (Exception $e) {
                $this->markAsError();
                $this->getElement('key')->addError($e->getMessage());
                $this->getElement('secret')->addError($e->getMessage());
                return false;
            }
        } else {
            $this->getElement('key')->setRequired(false);
            $this->getElement('secret')->setRequired(false);
        }

        return parent::isValid($data);
    }

    public function process()
    {
        pm_Settings::set('key', $this->getValue('key'));
        pm_Settings::set('secret', $this->getValue('secret'));
        pm_Settings::set('enabled', $this->getValue('enabled'));
    }
}