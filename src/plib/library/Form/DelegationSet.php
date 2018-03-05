<?php
// Copyright 1999-2018. Plesk International GmbH.
class Modules_Route53_Form_DelegationSet extends pm_Form_Simple
{
    public function init()
    {
        parent::init();

        $this->addElement('description', 'description', [
            'description' => $this->lmsg('createDelegationSetHint') . '<br>' .
                '<a href="http://docs.aws.amazon.com/Route53/latest/DeveloperGuide/white-label-name-servers.html" target="_blank">' . $this->lmsg('whiteLabel') . '</a>',
            'escape' => false,
        ]);

        $hostedZones = ['none' => $this->lmsg('hostedZoneNone')];
        $hostedZones = array_merge($hostedZones, array_flip(Modules_Route53_Client::factory()->getZones()));
        $this->addElement('select', 'hostedZone', [
            'label' => $this->lmsg('hostedZoneSelect'),
            'multiOptions' => $hostedZones,
        ]);

        $this->addControlButtons([
            'cancelLink' => pm_Context::getBaseUrl(),
        ]);
    }

    public function process()
    {
        $delegationSet = [
            'CallerReference' => uniqid(),
        ];
        if ('none' != $this->getValue('hostedZone')) {
            $delegationSet['HostedZoneId'] = $this->getValue('hostedZone');
        }
        $model = Modules_Route53_Client::factory()->createReusableDelegationSet($delegationSet);
        if (!pm_Settings::get('delegationSet')) {
            pm_Settings::set('delegationSet', $model['DelegationSet']['Id']);
        }
    }
}
