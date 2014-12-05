<?php
// Copyright 1999-2014. Parallels IP Holdings GmbH.
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
                $this->_checkApiConfig(['key' => $data['key'], 'secret' => $data['secret']]);
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

    private function _checkApiConfig($config)
    {
        $errorReporting = error_reporting(0);
        try {
            require_once __DIR__ . '/../externals/aws-autoloader.php';
            $client = \Aws\Route53\Route53Client::factory($config);
            $client->listHostedZones();
        } catch (Exception $e) {
            error_reporting($errorReporting);
            throw $e;
        }
        error_reporting($errorReporting);
    }

    public function process()
    {
        pm_Settings::set('key', $this->getValue('key'));
        pm_Settings::set('secret', $this->getValue('secret'));
        pm_Settings::set('enabled', $this->getValue('enabled'));
    }
}