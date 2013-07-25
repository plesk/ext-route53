<?php

class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        if (!pm_Session::getClient()->isAdmin()) {
            throw new pm_Exception('Permission denied');
        }
    }

	public function indexAction()
	{
        $this->view->pageTitle = 'AWS API Authorization';

        $form = new pm_Form_Simple();
        $form->addElement('text', 'key', array(
            'label' => 'Key',
            'value' => pm_Settings::get('key'),
            'class' => 'f-middle-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $form->addElement('text', 'secret', array(
            'label' => 'Secret',
            'value' => pm_Settings::get('secret'),
            'class' => 'f-middle-size',
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        $form->addElement('checkbox', 'enabled', array(
            'label' => 'Enable Amazon Web Service Route 53',
            'value' => pm_Settings::get('enabled'),
        ));

        $form->addControlButtons(array(
            'cancelLink' => pm_Context::getModulesListUrl(),
        ));

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            pm_Settings::set('key', $form->getValue('key'));
            pm_Settings::set('secret', $form->getValue('secret'));
            pm_Settings::set('enabled', $form->getValue('enabled'));

            $this->_status->addMessage('info', 'Data was successfully saved.');
            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
        }

        $this->view->form = $form;
	}
}
