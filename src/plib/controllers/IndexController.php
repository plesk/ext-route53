<?php
// Copyright 1999-2018. Plesk International GmbH.
require_once 'Zend/Controller/Action/Helper/Abstract.php';

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
            try {
                $res = $form->process();
                if ($res) {
                    $this->_status->addInfo($this->lmsg('iamUserCreated', ['userName' => $res['userName']]));
                } else {
                    $this->_status->addInfo($this->lmsg('authDataSaved'));
                }
            } catch (pm_Exception $e) {
                $this->_status->addError($e->getMessage());
            }
            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
        } else {
            pm_View_Status::addInfo($this->lmsg('statusRootAccountCredentials'));
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
                'title' => $this->lmsg('managedDomainsTitle'),
                'action' => 'managed-domain'
            ];
            $tabs[] = [
                'title' => $this->lmsg('toolsTitle'),
                'action' => 'tools',
            ];
        }
        return $tabs;
    }

    public function managedDomainAction()
    {
        $this->view->list = new Modules_Route53_List_ManagedDomains($this->view, $this->getRequest());
        $this->view->tabs = $this->_getTabs();
    }

    public function createManagedDomainAction()
    {
        $form = new Modules_Route53_Form_ManagedDomains();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            try {
                $form->process();
                $this->_status->addMessage('info', $this->lmsg('managedDomainCreated'));
            } catch (Exception $e) {
                $this->_status->addMessage('error', $e->getMessage());
            }
            $this->_helper->json(array('redirect' => $this->_helper->url('managed-domain')));
        }

        $this->view->pageTitle = $this->lmsg('createManagedDomainButton');
        $this->view->form = $form;
    }

    public function deleteManagedDomainAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        Modules_Route53_Settings::removeManagedDomainById((int)$this->_getParam('id'));

        $this->_redirect('index/managed-domain');
    }

    public function delegationSetAction()
    {
        $this->view->list = new Modules_Route53_List_DelegationSets($this->view, $this->getRequest());
        $this->view->tabs = $this->_getTabs();
    }

    public function delegationSetDataAction()
    {
        $list = new Modules_Route53_List_DelegationSets($this->view, $this->getRequest());
        $this->_helper->json($list->fetchData());
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
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }
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
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }
        pm_Settings::set('delegationSet', $this->_getParam('id'));
        $this->_status->addMessage('info', $this->lmsg('defaultDelegationSetChanged'));
        $this->_redirect('index/delegation-set');
    }

    public function toolsAction()
    {
        foreach (Modules_Route53_Logger::getErrorMessages() as $message) {
            $this->_status->addError($message);
        }

        $this->view->tools = [
            [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/refresh.png',
                'title' => $this->lmsg('syncAllButton'),
                'description' => $this->lmsg('syncAllHint'),
                'link' => "javascript:Modules_Route53_Confirm('{$this->_helper->url('sync-all')}', 'confirm', '{$this->lmsg('syncAllConfirm')}')",
            ], [
                'icon' => \pm_Context::getBaseUrl() . 'icons/32/remove-selected.png',
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

    protected function getDomainAliasListApi()
    {
        $res = [];
        $api = pm_ApiRpc::getService();
        $siteAliasRequest = '<site-alias><get><filter/></get></site-alias>';
        $siteAliasResponse = $api->call($siteAliasRequest);
        $alias = json_decode(json_encode($siteAliasResponse->{'site-alias'}->get));
        $aliasArray =  is_array($alias->result) ? $alias->result : array($alias->result);
        foreach ($aliasArray as $aliasDomain) {
            $res[] = $aliasDomain->info->name;
        }

        return $res;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getDomainListApi()
    {
        $res = [];
        $sitesRequest = '<site><get><filter/><dataset><gen_info/></dataset></get></site>';
        $webspRequest = '<webspace><get><filter/><dataset><gen_info/></dataset></get></webspace>';

        $api = pm_ApiRpc::getService();
        $sitesResponse = $api->call($sitesRequest);
        $webspResponse = $api->call($webspRequest);

        $sites = json_decode(json_encode($sitesResponse->site->get));
        $websp = json_decode(json_encode($webspResponse->webspace->get));

        $sitesArray =  is_array($sites->result) ? $sites->result : array($sites->result);
        $webspArray =  is_array($websp->result) ? $websp->result : array($websp->result);

        $tmpList = array_merge($sitesArray, $webspArray);

        foreach ($tmpList as $domain) {
            if (!isset($domain->id)) {
                continue;
            }
            $res[] = $domain->data->gen_info->name . '.';
        }

        return $res;
    }

    public function removeAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $domains = [];

        if (method_exists('pm_Domain', 'getAllDomains')) {
            $response = pm_Domain::getAllDomains();
            foreach ($response as $data) {
                $domainName = $data->getName();
                $domains[] = $domainName . '.';
            }
        } else {
            $domains = $this->getDomainListApi();
        }

        $domainAliases = $this->getDomainAliasListApi();
        $domains = array_merge($domains, $domainAliases, Modules_Route53_Settings::getManagedDomains());

        $client = Modules_Route53_Client::factory();
        $hostedZones = $client->getZones();

        foreach ($hostedZones as $zoneDomain => $zoneId) {
            if (!in_array($zoneDomain, $domains)) {
                continue;
            }

            $zoneChanges = $client->getHostedZoneRecordsToDelete($zoneId);
            if ($zoneChanges) {
                $client->changeResourceRecordSets([
                    'HostedZoneId' => $zoneId,
                    'ChangeBatch'  => [
                        'Changes' => $zoneChanges,
                    ],
                ]);
            }

            if (Modules_Route53_Settings::isManagedDomain($zoneDomain)) {
                continue;
            }

            $client->deleteHostedZone([
                'Id' => $zoneId,
            ]);
        }

        $this->_status->addMessage('info', $this->lmsg('removeAllDone'));
        $this->_redirect('index/tools');
    }
}
