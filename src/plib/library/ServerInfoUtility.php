<?php
// Copyright 1999-2018. Plesk International GmbH.

class Modules_Route53_ServerInfoUtility
{
    public static function getIpAddresses()
    {
        $addresses = [];
        $ipRequest = '<ip><get></get></ip>';

        $api = pm_ApiRpc::getService();
        $ipResponse = $api->call($ipRequest);
        $addressInfos = $ipResponse->xpath('/packet/ip/get/result/addresses/ip_info');

        foreach ($addressInfos as $addressInfo) {
            $address = (string)$addressInfo->public_ip_address;
            if ($address) {
                $addresses[$address] = strpos($address, '.') ? 'A' : 'AAAA';
            }
        }

        return $addresses;
    }
}
