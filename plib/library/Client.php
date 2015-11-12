<?php
// Copyright 1999-2015. Parallels IP Holdings GmbH.

/**
 * AWS PHP SDK
 * @link http://aws.amazon.com/sdk-for-php/
 */
require_once(__DIR__ . '/externals/aws-autoloader.php');

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

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_client, $method), $args);
    }

    public function checkCredentials()
    {
        $errorReporting = error_reporting(0);
        try {
            $this->_client->listHostedZones();
        } catch (Aws\Route53\Exception\Route53Exception $e) {
            error_reporting($errorReporting);
            if ('InvalidClientTokenId' == $e->getAwsErrorCode()) {
                throw new pm_Exception(pm_Locale::lmsg('invalidCredentials'));
            }
            throw $e;
        } catch (Exception $e) {
            error_reporting($errorReporting);
            throw $e;
        }
        error_reporting($errorReporting);
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
            $this->_zones = array();
            $model = $this->_client->listHostedZones();
            foreach ($model['HostedZones'] as $zone) {
                $this->_zones[$zone['Name']] = $zone['Id'];
            }
        }

        if (!array_key_exists($zoneName, $this->_zones)) {
            return null;
        }

        return $this->_zones[$zoneName];
    }

    public function createHostedZone(array $args = array())
    {
        $model = $this->_client->createHostedZone($args);
        $this->_zones[$model['HostedZone']['Name']] = $model['HostedZone']['Id'];
        return $model;
    }

    public function deleteHostedZone(array $args = array())
    {
        $model = $this->_client->deleteHostedZone($args);
        foreach ($this->_zones as $zoneName => $zoneId) {
            if (0 == strcmp($args['Id'], $zoneId)) {
                unset($this->_zones[$zoneName]);
            }
        }
        return $model;
    }

    public static function factory($config = array())
    {
        $config = array_merge(array(
            'credentials' => array(
                'key' => pm_Settings::get('key'),
                'secret' => pm_Settings::get('secret'),
            ),
            'version' => '2013-04-01',
            'region' => 'us-east-1',
        ), $config);
        return new self(new \Aws\Route53\Route53Client($config));
    }
}
