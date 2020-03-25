<?php
// Copyright 1999-2018. Plesk International GmbH.

/**
 * Class Settings
 *
 * Wrapper Class for Plesk Settings
 */
class Modules_Route53_Settings
{
    /**
     * Returns managedDomains from Plesk Settings
     *
     * @return array
     */
    public static function getManagedDomains()
    {
        $managedDomains = [];

        if ($managedDomainsSetting = pm_Settings::get('managedDomains')) {
            $managedDomains = explode(',', $managedDomainsSetting);
        }

        return $managedDomains;
    }

    /**
     * Sets managedDomains in Plesk settings
     *
     * @param array $managedDomains
     */
    public static function setManagedDomains($managedDomains)
    {
        pm_Settings::set('managedDomains', implode(',', $managedDomains));
    }

    /**
     * Adds managed domain to managedDomains in Plesk Settings
     *
     * @param string $managedDomain
     */
    public static function addManagedDomain($managedDomain)
    {
        $managedDomains = self::getManagedDomains();
        $managedDomains[] = $managedDomain;
        self::setManagedDomains($managedDomains);
    }

    /**
     * Remove managedDomain from managedDomains in Plesk Settings
     *
     * @param int $id
     */
    public static function removeManagedDomainById($id)
    {
        $managedDomains = self::getManagedDomains();
        unset($managedDomains[$id]);
        self::setManagedDomains($managedDomains);
    }
}
