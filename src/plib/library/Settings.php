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
        $managedDomain = strtolower($managedDomain);
        $managedDomain = substr($managedDomain, -1) === '.' ? $managedDomain : $managedDomain . '.';
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

    /**
     * Checks if subdomain is of any managedDomain
     *
     * @param string $subdomain
     * @return bool
     */
    public static function getManagedDomainOfSubdomain($subdomain)
    {
        // Domains end with an additional . so a subdomain has do contain at least 3 of them: sub.domain.tld.
        if (substr_count($subdomain, '.') < 3) {
            return '';
        }

        $managedDomains = self::getManagedDomains();
        $parts = array_reverse(array_filter(explode('.', $subdomain)));
        $domain = $parts[1] . '.' . $parts[0] . '.';

        return in_array($domain, $managedDomains, true) ? $domain : '';
    }

    /**
     * Check if domain is a managed domain
     *
     * @param string $domain
     * @return bool
     */
    public static function isManagedDomain($domain)
    {
        return in_array($domain, self::getManagedDomains(), true);
    }
}
