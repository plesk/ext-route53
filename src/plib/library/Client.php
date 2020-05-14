<?php
// Copyright 1999-2018. Plesk International GmbH.

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class Modules_Route53_Client
 *
 * @method \Aws\Result associateVPCWithHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateVPCWithHostedZoneAsync(array $args = [])
 * @method \Aws\Result changeResourceRecordSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise changeResourceRecordSetsAsync(array $args = [])
 * @method \Aws\Result changeTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise changeTagsForResourceAsync(array $args = [])
 * @method \Aws\Result createHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHealthCheckAsync(array $args = [])
 * @method \Aws\Result createHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHostedZoneAsync(array $args = [])
 * @method \Aws\Result createReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result deleteHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHealthCheckAsync(array $args = [])
 * @method \Aws\Result deleteHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHostedZoneAsync(array $args = [])
 * @method \Aws\Result deleteReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result disassociateVPCFromHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateVPCFromHostedZoneAsync(array $args = [])
 * @method \Aws\Result getChange(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getChangeAsync(array $args = [])
 * @method \Aws\Result getCheckerIpRanges(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCheckerIpRangesAsync(array $args = [])
 * @method \Aws\Result getGeoLocation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getGeoLocationAsync(array $args = [])
 * @method \Aws\Result getHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckAsync(array $args = [])
 * @method \Aws\Result getHealthCheckCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckCountAsync(array $args = [])
 * @method \Aws\Result getHealthCheckLastFailureReason(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckLastFailureReasonAsync(array $args = [])
 * @method \Aws\Result getHealthCheckStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckStatusAsync(array $args = [])
 * @method \Aws\Result getHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHostedZoneAsync(array $args = [])
 * @method \Aws\Result getHostedZoneCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHostedZoneCountAsync(array $args = [])
 * @method \Aws\Result getReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result listGeoLocations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listGeoLocationsAsync(array $args = [])
 * @method \Aws\Result listHealthChecks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHealthChecksAsync(array $args = [])
 * @method \Aws\Result listHostedZones(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHostedZonesAsync(array $args = [])
 * @method \Aws\Result listHostedZonesByName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHostedZonesByNameAsync(array $args = [])
 * @method \Aws\Result listResourceRecordSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listResourceRecordSetsAsync(array $args = [])
 * @method \Aws\Result listReusableDelegationSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listReusableDelegationSetsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listTagsForResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourcesAsync(array $args = [])
 * @method \Aws\Result updateHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHealthCheckAsync(array $args = [])
 * @method \Aws\Result updateHostedZoneComment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHostedZoneCommentAsync(array $args = [])
 */
class Modules_Route53_Client
{
    /** @var \Aws\Route53\Route53Client */
    private $_client;
    private $_zones = null;

    private function __construct(\Aws\Route53\Route53Client $client)
    {
        $this->_client = $client;
    }

    public function __call($method, array $args = [])
    {
        try {
            return call_user_func_array([$this->_client, $method], $args);
        } catch (Modules_Route53_Exception $e) {
            if (in_array($e->awsCode, ['Throttling', 'ServiceUnavailable', 'PriorRequestNotComplete'])) {
                // Rate limit of API requests exceeded
                sleep(10);
                return call_user_func_array([$this->_client, $method], $args);
            }
            throw $e;
        }
    }

    /**
     * @throws Modules_Route53_Exception with $awsCode = InvalidClientTokenId
     */
    public function checkCredentials()
    {
        $this->listHostedZones();
    }

    /**
     * Get Route53 zone id by zone name
     *
     * @param string $zoneName
     * @return string|null
     */
    public function getZoneId($zoneName)
    {
        if (null === $this->_zones) {
            $this->_zones = $this->getZones();
        }

        if (!array_key_exists($zoneName, $this->_zones)) {
            return null;
        }

        return $this->_zones[$zoneName];
    }

    public function getZones()
    {
        $zones = [];
        $opts = [/* 'MaxItems' => 2 */];
        do {
            $model = $this->listHostedZones($opts);
            foreach ($model['HostedZones'] as $zone) {
                $zones[$zone['Name']] = $zone['Id'];
            }
            $opts['Marker'] = $model['NextMarker'];
        } while ($model['IsTruncated']);
        return $zones;
    }

    public function getDelegationSets()
    {
        $delegationSets = [];
        $opts = [/* 'MaxItems' => 2 */];
        do {
            $model = $this->listReusableDelegationSets($opts);
            foreach ($model['DelegationSets'] as $delegationsSet) {
                $delegationSets[$delegationsSet['Id']] = $delegationsSet['NameServers'];
            }
            $opts['Marker'] = $model['NextMarker'];
        } while ($model['IsTruncated']);
        return $delegationSets;
    }
	
	public function getDelegationSetLimit($delegationsSetId)
	{
		$apiResponse = $this->__call('getReusableDelegationSetLimit', [[
			'DelegationSetId' => $delegationsSetId,
			'Type' => 'MAX_ZONES_BY_REUSABLE_DELEGATION_SET',
		]]);
		return (object) [
			'currentCount' => $apiResponse['Count'],
			'maxCount' => $apiResponse['Limit']['Value'],
		];
	}

    public function createHostedZone(array $args = [])
    {
        if ($delegationSetId = pm_Settings::get('delegationSet')) {
            // Workaround for Route53Client::cleanId
            $args['DelegationSetId'] = str_replace('/delegationset/', '', $delegationSetId);
        }
        $model = $this->__call('createHostedZone', [$args]);
        if (is_array($this->_zones)) {
            $this->_zones[$model['HostedZone']['Name']] = $model['HostedZone']['Id'];
        }
        return $model;
    }

    public function deleteHostedZone(array $args = [])
    {
        $model = $this->__call('deleteHostedZone', [$args]);
        if (is_array($this->_zones)) {
            foreach ($this->_zones as $zoneName => $zoneId) {
                if (0 == strcmp($args['Id'], $zoneId)) {
                    unset($this->_zones[$zoneName]);
                }
            }
        }
        return $model;
    }

    public function getHostedZoneRecordsToDelete($zoneId)
    {
        $recordSetIsTruncated = true;
        $nextRecordName = null;
        try {
            $changes = [];
            while ($recordSetIsTruncated) {
                $modelRRs = $this->listResourceRecordSets([
                    'HostedZoneId' => $zoneId,
                    'StartRecordName' => $nextRecordName,
                ]);

                $recordSetIsTruncated = $modelRRs['IsTruncated'];
                $nextRecordName = $modelRRs['NextRecordName'];

                foreach ($modelRRs['ResourceRecordSets'] as $modelRR) {

                    if (!in_array($modelRR['Type'], $this->getConfig()['supportedTypes'])) {
                        continue;
                    }

                    if($modelRR['Type'] == 'NS' || $modelRR['Type'] == 'SOA') {
                        // Route 53 does not support deletion of SOA or NS records
                        continue;
                    }

                    $changes[] = [
                        'Action' => 'DELETE',
                        'ResourceRecordSet' => $modelRR,
                    ];
                }
            }
            return $changes;
        } catch (Exception $e) {
            pm_Log::debug($e);
            return null;
        }
    }

    public function getConfig()
    {  // Integration config
        $configuration = [
            'ttl' => 300,  // Resource Records TTL
            'supportedTypes' => [  // Exportable Resource Record types
                'A',
                'TXT',
                'CNAME',
                'MX',
                'SRV',
                'SPF',
                'AAAA',
                'CAA',
            ],
            'createHostedZone' => true,  // Permission to create zone on AWS Route 53 billed
            'changeResourceRecordSets' => true,  // Permission to modify zone on AWS Route 53 free
            'deleteHostedZone' => true,  // Permission to delete zone on AWS Route 53 free
        ];
        if(pm_Settings::get('manageNsRecords')) {
            array_push($configuration['supportedTypes'], 'NS');
        }
        return $configuration;
    }

    public static function factory($config = [])
    {
        $config = array_merge([
            'exception_class' => 'Modules_Route53_Exception',
            'credentials' => [
                'key' => pm_Settings::get('key'),
                'secret' => pm_Settings::get('secret'),
            ],
            'version' => '2013-04-01',
            'region' => 'us-east-1',
        ], $config);

        if (pm_ProductInfo::isWindows()) {
            $caPath = __DIR__ . '/externals/cacert.pem';
            $caPath = str_replace('/', DIRECTORY_SEPARATOR, $caPath);
            $config = array_merge([
                'http' => ['verify' => $caPath],
            ], $config);
        }

        return new self(new \Aws\Route53\Route53Client($config));
    }
}
