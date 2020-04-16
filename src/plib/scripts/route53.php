<?php
// Copyright 1999-2018. Plesk International GmbH.
/**
 * This is the Amazon Route 53 integration script
 *
 * http://docs.aws.amazon.com/Route53/latest/APIReference
 */

pm_Loader::registerAutoload();
pm_Context::init('route53');

if (!pm_Settings::get('enabled')) {
    exit(0);
}

/**
 * Create AWS Route 53 client
 */
$client = Modules_Route53_Client::factory();

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

$log = new Modules_Route53_Logger();

foreach ($data as $record) {
    $zoneName = $realZoneName = $record->zone->name;
    $existingResources = [];

    if ($managedDomain = Modules_Route53_Settings::getManagedDomainOfSubdomain($zoneName)) {
        $zoneName = $managedDomain;
    }

    $recordsTTL = $record->zone->soa->ttl;

    //AWS Route 53 does not use uppercase letters
    $zoneId = $client->getZoneId(strtolower($zoneName));
    switch ($record->command) {
        /**
         * Zone created or updated
         */
        case 'create':
        case 'update':
            $changes = [];
            if (!$zoneId) {
                /**
                 * Zone not exists on AWS Route 53, create
                 */

                if (!$client->getConfig()['createHostedZone']) {
                    $log->warn("Skip zone {$zoneName}: createHostedZone not allowed in script.");
                    continue 2;
                }

                try {
                    $model = $client->createHostedZone(array(
                        'Name'            => $zoneName,
                        'CallerReference' => uniqid(),
                    ));
                } catch (Modules_Route53_Exception $e) {
                    if ('ConflictingDomainExists' == $e->awsCode) {
                        // TODO implement some workaround
                    }

                    $log->err("Failed zone creation {$zoneName}: {$e->getMessage()}");
                    continue 2;
                }

                $log->info("Zone created: {$zoneName}\n");

                $zoneId = $model['HostedZone']['Id'];

            } else {
                try {
                    $resourceRecordSets = $client->getHostedZoneRecordSets(substr($zoneId, 12));

                    foreach ($resourceRecordSets as $resourceRecordSet) {
                        $existingResources["CREATE {$resourceRecordSet['Name']} {$resourceRecordSet['Type']}"] = [
                            'Action' => 'CREATE',
                            'ResourceRecordSet' => [
                                'Name' => $resourceRecordSet['Name'],
                                'Type' => $resourceRecordSet['Type'],
                                'TTL' => $resourceRecordSet['TTL'],
                                'ResourceRecords' => $resourceRecordSet['ResourceRecords']
                            ]
                        ];
                    }
                } catch (Modules_Route53_Exception $e) {
                    $log->err("Zone {$zoneName} not found: {$e->getMessage()}\n");
                    continue 2;
                }
            }

            /**
             * Add Resource records to zone
             */
            foreach($record->zone->rr as $rr) {
                if (!in_array($rr->type, $client->getConfig()['supportedTypes'])) {
                    continue;
                }

                $uid = "CREATE {$rr->host} {$rr->type}";

                if (!array_key_exists($uid, $changes)) {
                    $changes[$uid] = array(
                        'Action' => 'CREATE',
                        'ResourceRecordSet' => array(
                            'Name' => $rr->host,
                            'Type' => $rr->type,
                            'TTL' => $recordsTTL,
                            'ResourceRecords' => array(),
                        ),
                    );
                }

                if ("0" === $rr->opt) {
                    // Workaround cast zero to boolean false
                    $opt = "0 ";
                } elseif ($rr->opt) {
                    $opt = "{$rr->opt} ";
                } else {
                    $opt = '';
                }

                if ('TXT' == $rr->type) {
                    $rr->value = trim($rr->value);
                    $rr->value = str_replace("\t", ' ', $rr->value);
                    /**
                     * AWS Route 53 requires quotation of the TXT Resource Record value
                     * Max unsplitted TXT length should be 255
                     */
                    $value = array_reduce(str_split($opt . $rr->value, 255), function ($carry, $chunk) {
                        return ($carry == '' ? '' : $carry . ' ') . '"' . $chunk . '"';
                    }, '');
                } elseif ('CAA' == $rr->type) {
                    $value = "{$opt}\"{$rr->value}\"";
                } else {
                    $value = "{$opt}{$rr->value}";
                }

                $changes[$uid]['ResourceRecordSet']['ResourceRecords'][] = array('Value' => $value);
            }

            if (!$client->getConfig()['changeResourceRecordSets']) {
                $log->warn("Skip zone {$zoneName}: changeResourceRecordSets not allowed in script.\n");
                continue 2;
            }

            $hostedZoneRecordsToDelete = $client->getHostedZoneRecordsToDelete($zoneId);

            // Remove changes that already exist
            foreach ($changes as $key => $change) {
                if ($existingResourceRecordSet = $existingResources[$key]) {
                    if ($existingResourceRecordSet !== $change) {
                        $hostedZoneRecordToDelete = getRecordSetFromRecordSets(
                            $hostedZoneRecordsToDelete,
                            $change['ResourceRecordSet']['Name'],
                            $change['ResourceRecordSet']['Type']
                        );

                        if ( $hostedZoneRecordToDelete['ResourceRecordSet'] !== $change['ResourceRecordSet']) {
                            array_unshift($changes, $hostedZoneRecordToDelete);
                        }
                    } else {
                        unset($changes[$key]);
                    }
                }
            }

            /**
             * Apply zone modification
             */
            try {
                if ($changes) {
                    $client->changeResourceRecordSets(array(
                        'HostedZoneId' => $zoneId,
                        'ChangeBatch'  => array(
                            'Changes' => $changes
                        ),
                    ));
                }
            } catch (Modules_Route53_Exception $e) {
                $log->err("Failed zone update {$zoneName}: {$e->getMessage()}\n");
                continue 2;
            }

            $log->info("ResourceRecordSet updated: {$zoneName}\n");

            break;

        case 'delete':
            if (!$zoneId || $realZoneName !== $zoneName) {
                continue 2;
            }

            if ($client->getConfig()['changeResourceRecordSets']) {

                $changes = $client->getHostedZoneRecordsToDelete($zoneId);
                try {
                    if ($changes) {
                        $client->changeResourceRecordSets(array(
                            'HostedZoneId' => $zoneId,
                            'ChangeBatch' => array(
                                'Changes' => $changes,
                            ),
                        ));
                    }
                } catch (Modules_Route53_Exception $e) {
                    $log->err("Failed zone removal {$zoneName}: {$e->getMessage()}\n");
                    continue 2;
                }
            }

            if (!$client->getConfig()['deleteHostedZone']) {
                $log->warn("Skip zone {$zoneName}: deleteHostedZone not allowed in script.\n");
                continue 2;
            }

            try {
                $client->deleteHostedZone(array(
                    'Id' => $zoneId,
                ));
            } catch (Modules_Route53_Exception $e) {
                $log->err("Failed zone removal {$zoneName}: {$e->getMessage()}\n");
                continue 2;
            }

            $log->info("Zone deleted: {$zoneName}\n");
            break;
    }
}

function getRecordSetFromRecordSets($recordSets, $name, $type)
{
    foreach ($recordSets as $recordSet) {
        if (
            $recordSet['ResourceRecordSet']['Name'] === $name
            && $recordSet['ResourceRecordSet']['Type'] === $type
        ) {
            return $recordSet;
        }
    }

    return [];
}



if ($log->hasErrors()) {
    exit(255);
}
