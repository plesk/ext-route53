<?php
// Copyright 1999-2015. Parallels IP Holdings GmbH.
class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        if (!pm_Session::getClient()->isAdmin()) {
            throw new pm_Exception('Permission denied');
        }
        $this->view->pageTitle = $this->lmsg('pageTitle');
    }

    public function indexAction()
    {
        $form = new Modules_Route53_Form_Settings();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $form->process();

            $this->_status->addMessage('info', $this->lmsg('authDataSaved'));
            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
        }

        $this->view->form = $form;
        $this->view->tabs = $this->_getTabs();
    }

    private function _getTabs()
    {
        $tabs = [];
        $tabs[] = [
            'title' => $this->lmsg('indexPageTitle'),
            'action' => 'index',
        ];
        if (pm_Settings::get('enabled')) {
            $tabs[] = [
                'title' => $this->lmsg('delegationSetTitle'),
                'action' => 'delegation-set',
            ];
        }
        return $tabs;
    }

    public function delegationSetAction()
    {
        $data = [];
        $delegationsSets = Modules_Route53_Client::factory()->listReusableDelegationSets();
        // TODO check NextMarker
        foreach ($delegationsSets['DelegationSets'] as $delegationsSet) {
            $delegationsSetId = urlencode($delegationsSet['Id']);
            $data[] = [
                'nameServers' => implode("<br>", $delegationsSet['NameServers']),
                'actions' => implode("<br>", [
                    // TODO use as default
                    // TODO recreate all zones
                    "<a href='" . $this->_helper->url('delete-delegation-set') . "/id/$delegationsSetId'>" .
                        $this->lmsg('deleteDelegationSetButton') .
                    "</a>",
                ]),
            ];
        }

        $list = new pm_View_List_Simple($this->view, $this->getRequest());
        $list->setColumns([
            'nameServers' => [
                'title' => $this->lmsg('nameServersColumn'),
                'noEscape' => true,
            ],
            'actions' => [
                'title' => $this->lmsg('actionsColumn'),
                'noEscape' => true,
            ],
        ]);
        $list->setData($data);
        $list->setTools([[
            'title' => $this->lmsg('createDelegationSetButton'),
            'description' => $this->lmsg('createDelegationSetHint'),
            'action' => 'create-delegation-set',
            'class' => 'sb-item-add',
        ]]);

        $this->view->list = $list;
        $this->view->tabs = $this->_getTabs();
    }

    public function createDelegationSetAction()
    {
        $form = new Modules_Route53_Form_DelegationSet();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            try {
                $form->process();
                $this->_status->addMessage('info', $this->lmsg('delegationSetCreated'));
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
            $this->_helper->json(array('redirect' => $this->_helper->url('delegation-set')));
        }

        $this->view->pageTitle = $this->lmsg('createDelegationSetButton');
        $this->view->form = $form;
    }

    public function deleteDelegationSetAction()
    {
        $delegationSet = [
            'Id' => $this->_getParam('id'),
        ];
        try {
            Modules_Route53_Client::factory()->deleteReusableDelegationSet($delegationSet);
            $this->_status->addMessage('info', $this->lmsg('delegationSetDeleted'));
        } catch (Exception $e) {
            $this->_status->addMessage('error', $e->getMessage());
        }
        $this->_redirect('index/delegation-set');
    }
}
