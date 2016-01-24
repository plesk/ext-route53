<?php
// Copyright 1999-2015. Parallels IP Holdings GmbH.
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
            'actions' => [
                'title' => $this->lmsg('actionsColumn'),
                'noEscape' => true,
            ],
        ]);

        $this->setData($this->_getData($view));

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

    private function _getData($view)
    {
        $data = [];
        foreach (Modules_Route53_Client::factory()->getDelegationSets() as $delegationsSetId => $nameServers) {
            $urlId = urlencode($delegationsSetId);
            $isDefault = $delegationsSetId == pm_Settings::get('delegationSet');
            $data[] = [
                'nameServers' => implode("<br>", $nameServers),
                'actions' => implode("<br>", [
                    $isDefault
                        ? "<b>" . $this->lmsg('defaultDelegationSet') . "</b>"
                        : "<a class='s-btn sb-activate' data-method='post'" .
                        " href='{$view->url(['action' => 'default-delegation-set'])}/id/$urlId'>" .
                        "<span>" . $this->lmsg('defaultDelegationSetButton') . "</span>" .
                        "</a>",
                    "<a class='s-btn sb-delete' data-method='post'" .
                    " href='{$view->url(['action' => 'delete-delegation-set'])}/id/$urlId'>" .
                    "<span>" . $this->lmsg('deleteDelegationSetButton') . "</span>" .
                    "</a>",
                ]),
            ];
        }
        return $data;
    }
}
