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
        $result = $this->getSesV2Client()->createEmailIdentity(['EmailIdentity' => $domain]);

         if ($result->hasKey('DkimAttributes') && array_key_exists('Tokens', $result->get('DkimAttributes'))) {
            $request = <<<APICALL
<packet>
<dns>
APICALL;
             foreach ($result->get('DkimAttributes')['Tokens'] as $token) {
                 $request .= <<<APICALL
   <add_rec>
      <site-id>${siteId}</site-id>
      <type>CNAME</type>
      <host>${token}._domainkey</host>
      <value>${token}.dkim.amazonses.com</value>
   </add_rec>
APICALL;
             }
                $request .= <<<APICALL
</dns>
</packet>
APICALL;
                pm_ApiRpc::getService('1.6.8.0')->call($request);
        }
    }

    private function removeAmazonSesVerificationEntry(string $domain)
    {
        $this->getSesV2Client()->deleteEmailIdentity(['EmailIdentity' => $domain]);
    }

    private function getSesV2Client()
    {
        return new \Aws\SesV2\SesV2Client([
            'exception_class' => 'Modules_Route53_Exception',
            'credentials' => [
                'key' => pm_Settings::get('key'),
                'secret' => pm_Settings::get('secret'),
            ],
            'version' => '2019-09-27',
            'region' => pm_Settings::get('region'),
        ]);
    }
}

return new Modules_Route53_EventListener();
