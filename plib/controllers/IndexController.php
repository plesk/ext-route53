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
        /** @noinspection PhpUndefinedFieldInspection */
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
            $tabs[] = [
                'title' => $this->lmsg('toolsTitle'),
                'action' => 'tools',
            ];
        }
        return $tabs;
    }

    public function delegationSetAction()
    {
        $data = [];
        foreach (Modules_Route53_Client::factory()->getDelegationSets() as $delegationsSetId => $nameServers) {
            $urlId = urlencode($delegationsSetId);
            $isDefault = $delegationsSetId == pm_Settings::get('delegationSet');
            $data[] = [
                'nameServers' => implode("<br>", $nameServers),
                'actions' => implode("<br>", [
                    $isDefault ? "<b>" . $this->lmsg('defaultDelegationSet') . "</b>"
                        : "<a href='" . $this->_helper->url('default-delegation-set') . "/id/$urlId'>" .
                            $this->lmsg('defaultDelegationSetButton') .
                        "</a>",
                    // TODO recreate all zones
                    "<a href='" . $this->_helper->url('delete-delegation-set') . "/id/$urlId'>" .
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
        ], [
            'title' => $this->lmsg('resetDefaultDelegationSetButton'),
            'description' => $this->lmsg('resetDefaultDelegationSetHint'),
            'action' => 'default-delegation-set',
            'class' => 'sb-revert',
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
            if ($delegationSet['Id'] == pm_Settings::get('delegationSet')) {
                pm_Settings::set('delegationSet', null);
            }
            $this->_status->addMessage('info', $this->lmsg('delegationSetDeleted'));
        } catch (Exception $e) {
            $this->_status->addMessage('error', $e->getMessage());
        }
        $this->_redirect('index/delegation-set');
    }

    public function defaultDelegationSetAction()
    {
        pm_Settings::set('delegationSet', $this->_getParam('id'));
        $this->_status->addMessage('info', $this->lmsg('defaultDelegationSetChanged'));
        $this->_redirect('index/delegation-set');
    }

    public function toolsAction()
    {
        $this->view->tools = [
            [
                'icon' => '/theme/icons/32/plesk/refresh.png',
                'title' => $this->lmsg('syncAllButton'),
                'description' => $this->lmsg('syncAllHint'),
                'link' => "javascript:Modules_Route53_Confirm('{$this->_helper->url('sync-all')}', 'confirm', '{$this->lmsg('syncAllConfirm')}')",
            ], [
                'icon' => '/theme/icons/32/plesk/remove-selected.png',
                'title' => $this->lmsg('removeAllButton'),
                'description' => $this->lmsg('removeAllHint'),
                'link' => "javascript:Modules_Route53_Confirm('{$this->_helper->url('remove-all')}', 'delete', '{$this->lmsg('removeAllConfirm')}')",
            ],
        ];
        $this->view->tabs = $this->_getTabs();
    }

    public function syncAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }
        // Workaround with internal classes because pm_ApiCli is not supported outside of CLI
        require_once('api-common/cuDns.php');
        $cu = new cuDNS();
        $cu->syncAllZones();

        $this->_status->addMessage('info', $this->lmsg('syncAllDone'));
        $this->_redirect('index/tools');
    }

    public function removeAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }
        $client = Modules_Route53_Client::factory();
        foreach ($client->getZones() as $zoneId) {
            $modelRRs = $client->listResourceRecordSets([
                'HostedZoneId' => $zoneId,
            ]);
            $zoneChanges = [];
            foreach ($modelRRs['ResourceRecordSets'] as $modelRR) {
                if (in_array($modelRR['Type'], ['SOA', 'NS'])) {
                    continue;
                }
                $zoneChanges[] = [
                    'Action' => 'DELETE',
                    'ResourceRecordSet' => $modelRR,
                ];
            }
            $client->changeResourceRecordSets([
                'HostedZoneId' => $zoneId,
                'ChangeBatch' => [
                    'Changes' => $zoneChanges,
                ],
            ]);
            $client->deleteHostedZone([
                'Id' => $zoneId,
            ]);
        }

        $this->_status->addMessage('info', $this->lmsg('removeAllDone'));
        $this->_redirect('index/tools');
    }
}
