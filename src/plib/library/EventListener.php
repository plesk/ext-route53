<?php

/**
 * Event listener for route53 integration.
 *
 * Handles site_create and site_remove events to add and remove Amazon SES verification tokens.
 * Required permissions: ses:VerifyDomainIdentity, ses:DeleteIdentity
 */
class Modules_Route53_EventListener implements EventListener
{
    public function filterActions()
    {
        return [
            'site_create',
            'site_delete'
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValues, $newValues)
    {
        switch ($action) {
            case 'site_create' :
                $this->addAmazonSesVerificationEntry($newValues['Domain Name'], (int)$objectId);
                break;
            case 'site_delete':
                $this->removeAmazonSesVerificationEntry($oldValues['Domain Name']);
                break;
        }
    }

    /**
     * Get verification token from AWS SES API and set it as TXT entry for the new domain
     *
     * @param string $domain
     * @param int $siteId
     */
    private function addAmazonSesVerificationEntry(string $domain, int $siteId)
    {
        $request = <<<APICALL
<packet>
<dns>
 <get_rec>
  <filter>
    <site-id>${siteId}</site-id>
  </filter>
 </get_rec>
</dns>
</packet>
APICALL;
        $response = pm_ApiRpc::getService('1.6.8.0')->call($request);
        $oldVerificationToken = '';
        foreach($response->dns->get_rec as $record) {
            if (strpos('_amazonses', $record->data->host) !== false) {
                $oldVerificationToken = $record->data->value;
                break;
            }
        }
        $result = $this->getSesClient()->verifyDomainIdentity(['Domain' => $domain]);
        if ($result->hasKey('VerificationToken')) {
            $verificationToken = $result->get('VerificationToken');
            if ($verificationToken !== $oldVerificationToken) {
                $request = <<<APICALL
<packet>
<dns>
   <add_rec>
      <site-id>${siteId}</site-id>
      <type>TXT</type>
      <host>_amazonses</host>
      <value>${verificationToken}</value>
   </add_rec>
</dns>
</packet>
APICALL;
            }
        }
    }

    private function removeAmazonSesVerificationEntry(string $domain)
    {
        $this->getSesClient()->deleteIdentity(['Identity' => $domain]);
    }

    private function getSesClient()
    {
        return new \Aws\Ses\SesClient([
            'exception_class' => 'Modules_Route53_Exception',
            'credentials' => [
                'key' => pm_Settings::get('key'),
                'secret' => pm_Settings::get('secret'),
            ],
            'version' => '2010-12-01',
            'region' => pm_Settings::get('region'),
        ]);
    }
}

return new Modules_Route53_EventListener();
