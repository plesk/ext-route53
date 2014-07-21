<?php
// Copyright 1999-2014. Parallels IP Holdings GmbH. All Rights Reserved.
/**
 * This is the Amazon Route 53 integration script
 *
 * http://docs.aws.amazon.com/Route53/latest/APIReference
 */

pm_Loader::registerAutoload();
pm_Context::init('route53');

/**
 * AWS PHP SDK
 * http://aws.amazon.com/sdkforphp/
 */
require_once __DIR__ . '/../library/externals/aws-autoloader.php';

if (!pm_Settings::get('enabled')) {
    exit(0);
}

/**
 * Integration config
 */
$config = array(
    /**
     * AWS API Authorization
     * https://portal.aws.amazon.com/ MyAccount -> Security Credentials
     */
    'client' => array(
        'key' => pm_Settings::get('key'),
        'secret' => pm_Settings::get('secret'),
    ),
    /**
     * Resource Records TTL
     */
    'ttl' => 300,
    /**
     * Exportable Resource Record types
     */
    'supportedTypes' => array(
        'A',
        'TXT',
        'CNAME',
        'MX',
        'SRV',
        'SPF',
        'AAAA',
//        'SOA',
//        'NS',
    ),
    /**
     * Permission to create zone on AWS Route 53
     * billed
     */
    'createHostedZone' => true,
    /**
     * Permission to modify zone on AWS Route 53
     * free
     */
    'changeResourceRecordSets' => true,
    /**
     * Permission to delete zone on AWS Route 53
     * free
     */
    'deleteHostedZone' => true,
);

/**
 * Create AWS Route 53 client
 */
$client = \Aws\Route53\Route53Client::factory($config['client']);

/**
 * Get Route53 zone id by zone name
 *
 * @param \Aws\Route53\Route53Client $client
 * @param string $zoneName
 * @return string|null
 */
function getModelZoneId(\Aws\Route53\Route53Client $client, $zoneName)
{
    static $zones = null;
    if (null === $zones) {
        $zones = array();
        $model = $client->listHostedZones();
        foreach ($model['HostedZones'] as $zone) {
            $zones[$zone['Name']] = $zone['Id'];
        }
    }

    if (!array_key_exists($zoneName, $zones)) {
        return null;
    }

    return $zones[$zoneName];
}

/**
 * Read zone script from stdin
 *
 *[
 * {
 *  "command": "(update|delete)",
 *  "zone": {
 *      "name": "domain.tld.",
 *      "displayName": "domain.tld.",
 *      "soa": {
 *          "email": "email@address",
 *          "status": 0,
 *          "type": "master",
 *          "ttl": 86400,
 *          "refresh": 10800,
 *          "retry": 3600,
 *          "expire": 604800,
 *          "minimum": 10800,
 *          "serial": 123123123,
 *          "serial_format": "UNIXTIMESTAMP"
 *      },
 *      "rr": [{
 *          "host": "www.domain.tld.",
 *          "displayHost": "www.domain.tld.",
 *          "type": "CNAME",
 *          "displayValue": "domain.tld.",
 *          "opt": "",
 *          "value": "domain.tld."
 *      }]
 * }, {
 *  "command": "(createPTRs|deletePTRs)",
 *  "ptr": {
 *      "ip_address": "1.2.3.4",
 *      "hostname": "domain.tld"}
 * }
 *]
 */
$data = json_decode(file_get_contents('php://stdin'));
//Example:
//[
//    {"command": "update", "zone": {"name": "domain.tld.", "displayName": "domain.tld.", "soa": {"email": "amihailov@parallels.com", "status": 0, "type": "master", "ttl": 86400, "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1363228965, "serial_format": "UNIXTIMESTAMP"}, "rr": [
//        {"host": "www.domain.tld.", "displayHost": "www.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "1.2.3.4", "displayHost": "1.2.3.4", "type": "PTR", "displayValue": "domain.tld.", "opt": "24", "value": "domain.tld."},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "TXT", "displayValue": "v=spf1 +a +mx -all", "opt": "", "value": "v=spf1 +a +mx -all"},
//        {"host": "ftp.domain.tld.", "displayHost": "ftp.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "ipv4.domain.tld.", "displayHost": "ipv4.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "mail.domain.tld.", "displayHost": "mail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "MX", "displayValue": "mail.domain.tld.", "opt": "10", "value": "mail.domain.tld."},
//        {"host": "webmail.domain.tld.", "displayHost": "webmail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "ns.domain.tld.", "displayHost": "ns.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"}
//    ]}},
//    {"command": "createPTRs", "ptr": {"ip_address": "1.2.3.4", "hostname": "domain.tld"}},
//    {"command": "createPTRs", "ptr": {"ip_address": "2002:5bcc:18fd:000c:0001:0002:0003:0004", "hostname": "domain.tld"}}
//]

foreach ($data as $record) {

    switch ($record->command) {
        /**
         * Zone created or updated
         */
        case 'create':
        case 'update':
            $zoneId = getModelZoneId($client, $record->zone->name);

            $changes = array();
            if (!$zoneId) {

                /**
                 * Zone not exists on AWS Route 53, create
                 */

                if (!$config['createHostedZone']) {
                    echo("Skip zone {$record->zone->name}: createHostedZone not allowed in script.\n");
                    continue;
                }

                $model = $client->createHostedZone(array(
                    'Name' => $record->zone->name,
                    'CallerReference' => uniqid(),
                ));

                echo("Zone created: {$record->zone->name}\n");

                $zoneId = $model['HostedZone']['Id'];

            } else {

                /**
                 * Zone exists, remove old Resource Records
                 */

                $modelRRs = $client->listResourceRecordSets(array(
                    'HostedZoneId' => $zoneId,
                ));

                foreach ($modelRRs['ResourceRecordSets'] as $modelRR) {

                    if (!in_array($modelRR['Type'], $config['supportedTypes'])) {
                        continue;
                    }

                    $changes[] = array(
                        'Action' => 'DELETE',
                        'ResourceRecordSet' => $modelRR,
                    );

                }
            }

            /**
             * Add Resource records to zone
             */

            foreach($record->zone->rr as $rr) {

                if (!in_array($rr->type, $config['supportedTypes'])) {
                    continue;
                }

                $uid = "CREATE {$rr->host} {$rr->type}";

                if (!array_key_exists($uid, $changes)) {
                    $changes[$uid] = array(
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => array(
                            'Name' => $rr->host,
                            'Type' => $rr->type,
                            'TTL' => $config['ttl'],
                            'ResourceRecords' => array(),
                        ),
                    );
                }
                $opt = $rr->opt ? "{$rr->opt} " : '';
                if ('TXT' == $rr->type) {
                    /**
                     * AWS Route 53 requires quotation of the TXT Resource Record value
                     */
                    $value = "\"{$opt}{$rr->value}\"";
                } else {
                    $value = "{$opt}{$rr->value}";
                }

                $changes[$uid]['ResourceRecordSet']['ResourceRecords'][] = array('Value' => $value);
            }

            if (!$config['changeResourceRecordSets']) {
                echo("Skip zone {$record->zone->name}: changeResourceRecordSets not allowed in script.\n");
                continue;
            }

            /**
             * Apply zone modification
             */

            $model = $client->changeResourceRecordSets(array(
                'HostedZoneId' => $zoneId,
                'ChangeBatch' => array(
                    'Changes' => $changes,
                ),
            ));

            echo("ResourceRecordSet updated: {$record->zone->name}\n");

            break;

        case 'delete':
            $zoneId = getModelZoneId($client, $record->zone->name);

            if (!$zoneId) {
                continue;
            }

            if ($config['changeResourceRecordSets']) {

                $modelRRs = $client->listResourceRecordSets(array(
                    'HostedZoneId' => $zoneId,
                ));

                $changes = array();
                foreach ($modelRRs['ResourceRecordSets'] as $modelRR) {
                    if (!in_array($modelRR['Type'], $config['supportedTypes'])) {
                        continue;
                    }
                    $changes[] = array(
                        'Action' => 'DELETE',
                        'ResourceRecordSet' => $modelRR,
                    );
                }

                $model = $client->changeResourceRecordSets(array(
                    'HostedZoneId' => $zoneId,
                    'ChangeBatch' => array(
                        'Changes' => $changes,
                    ),
                ));
            }

            if (!$config['deleteHostedZone']) {
                echo("Skip zone {$record->zone->name}: deleteHostedZone not allowed in script.\n");
                continue;
            }

            $client->deleteHostedZone(array(
                'Id' => $zoneId,
            ));

            echo("Zone deleted: {$record->zone->name}\n");
            break;
    }
}
