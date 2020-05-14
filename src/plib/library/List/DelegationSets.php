<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_List_DelegationSets extends pm_View_List_Simple
{
    public function __construct($view, $request, $options = [])
    {
        parent::__construct($view, $request, $options);

        $this->setColumns([
            'nameServers' => [
                'title' => $this->lmsg('nameServersColumn'),
                'noEscape' => true,
            ],
            'ipAddresses' => [
                'title' => $this->lmsg('ipAddressesColumn'),
                'noEscape' => true,
            ],
            'info' => [
                'title' => $this->lmsg('infoColumn'),
                'noEscape' => true,
            ],
            'actions' => [
                'title' => $this->lmsg('actionsColumn'),
                'noEscape' => true,
            ],
        ]);

        $this->setData($this->_getRecords($view));
        $this->setDataUrl($view->url(['action' => 'delegation-set-data']));

        $this->setTools([
            [
                'title' => $this->lmsg('createDelegationSetButton'),
                'description' => $this->lmsg('createDelegationSetHint'),
                'action' => 'create-delegation-set',
                'class' => 'sb-item-add',
            ],
            [
                'title' => $this->lmsg('resetDefaultDelegationSetButton'),
                'description' => $this->lmsg('resetDefaultDelegationSetHint'),
                'link' => "javascript:Jsw.redirectPost('{$view->url(['action' => 'default-delegation-set'])}')",
                'class' => 'sb-revert',
            ]
        ]);
    }

    private function _getRecords($view)
    {
        $data = [];
        foreach (Modules_Route53_Client::factory()->getDelegationSets() as $delegationsSetId => $nameServers) {
            $ipAddresses = array_map('gethostbyname', $nameServers);
            $urlId = urlencode($delegationsSetId);
            $isDefault = $delegationsSetId == pm_Settings::get('delegationSet');
            $limit = Modules_Route53_Client::factory()->getDelegationSetLimit($delegationsSetId);
            $data[] = [
                'nameServers' => implode("<br>", $nameServers),
                'ipAddresses' => implode("<br>", $ipAddresses),
                'actions' => implode("<br>", [
                    $isDefault
                        ? "<b>" . $this->lmsg('defaultDelegationSet') . "</b>"
                        : "<a class='s-btn sb-activate' data-method='post'" .
                        " href='{$view->url(['action' => 'default-delegation-set'])}?id=$urlId'>" .
                        "<span>" . $this->lmsg('defaultDelegationSetButton') . "</span>" .
                        "</a>",
                    "<a class='s-btn sb-delete' data-method='post'" .
                    " href='{$view->url(['action' => 'delete-delegation-set'])}?id=$urlId'>" .
                    "<span>" . $this->lmsg('deleteDelegationSetButton') . "</span>" .
                    "</a>",
                ]),
                'info' => "Id: " . htmlentities($delegationsSetId) . "<br>" . $this->lmsg('delegationSetLimit') . ": " . $limit->currentCount . " / " . $limit->maxCount,
            ];
        }
        return $data;
    }
}
