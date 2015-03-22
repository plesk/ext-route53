<?php
// Copyright 1999-2015. Parallels IP Holdings GmbH.

/**
 * AWS PHP SDK
 * http://aws.amazon.com/sdkforphp/
 */
require_once(__DIR__ . '/externals/aws-autoloader.php');

/**
 * Class Modules_Route53_Client
 *
 * @method Guzzle\Service\Resource\Model changeResourceRecordSets(array $args = array())
 * @method Guzzle\Service\Resource\Model changeTagsForResource(array $args = array())
 * @method Guzzle\Service\Resource\Model createHealthCheck(array $args = array())
 * @method Guzzle\Service\Resource\Model deleteHealthCheck(array $args = array())
 * @method Guzzle\Service\Resource\Model getChange(array $args = array())
 * @method Guzzle\Service\Resource\Model getCheckerIpRanges(array $args = array())
 * @method Guzzle\Service\Resource\Model getHealthCheck(array $args = array())
 * @method Guzzle\Service\Resource\Model getHealthCheckCount(array $args = array())
 * @method Guzzle\Service\Resource\Model getHostedZone(array $args = array())
 * @method Guzzle\Service\Resource\Model listHealthChecks(array $args = array())
 * @method Guzzle\Service\Resource\Model listHostedZones(array $args = array())
 * @method Guzzle\Service\Resource\Model listResourceRecordSets(array $args = array())
 * @method Guzzle\Service\Resource\Model listTagsForResource(array $args = array())
 * @method Guzzle\Service\Resource\Model listTagsForResources(array $args = array())
 * @method Guzzle\Service\Resource\Model updateHealthCheck(array $args = array())
 * @method Guzzle\Service\Resource\ResourceIteratorInterface getListHealthChecksIterator(array $args = array())
 * @method Guzzle\Service\Resource\ResourceIteratorInterface getListHostedZonesIterator(array $args = array())
 * @method Guzzle\Service\Resource\ResourceIteratorInterface getListResourceRecordSetsIterator(array $args = array())
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
            'key' => pm_Settings::get('key'),
            'secret' => pm_Settings::get('secret'),
        ), $config);
        return new self(\Aws\Route53\Route53Client::factory($config));
    }
}
