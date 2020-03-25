<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_List_ManagedDomains extends pm_View_List_Simple
{
    public function __construct($view, $request, $options = [])
    {
        parent::__construct($view, $request, $options);

        $this->setColumns([
            'managedDomain' => [
                'title' => $this->lmsg('managedDomainColumn'),
                'noEscape' => true,
            ],
            'actions' => [
                'title' => $this->lmsg('actionsColumn'),
                'noEscape' => true,
            ],
        ]);

        $this->setData($this->_getRecords($view));
        $this->setDataUrl($view->url(['action' => 'managed-domain-data']));

        $this->setTools([
            [
                'title' => $this->lmsg('createManagedDomainButton'),
                'description' => $this->lmsg('createManagedDomainHint'),
                'action' => 'create-managed-domain',
                'class' => 'sb-item-add',
            ],
        ]);
    }

    private function _getRecords($view)
    {
        $managedDomains = Modules_Route53_Settings::getManagedDomains();
        $data = [];

        foreach ($managedDomains as $key => $managedDomain) {
            $data[] = [
                'managedDomain' => $managedDomain,
                'actions' =>
                    "<a class='s-btn sb-delete' data-method='post'"
                    . " href='{$view->url(['action' => 'delete-managed-domain'])}?id=$key'>"
                    . "<span>"
                    . $this->lmsg('deleteManagedDomainButton')
                    . "</span>"
                    . "</a>"
            ];
        }
        return $data;
    }
}
