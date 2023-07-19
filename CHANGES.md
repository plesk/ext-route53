# 2.9.1

* [+] Integration with AWS root account is not available anymore because of security and recommendation to avoid using this way.

# 2.9.0

* [*] Internal improvements.

# 2.8.2

* [-] The extension now does not produce PHP error messages regarding AWS. (EXTPLESK-4242)

# 2.8.1

* [-] The extension now correctly syncs domains` DNS records if the total number of records exceeds 100. (EXTPLESK-2698)
* [-] The extension now syncs a public zone instead of a private one for a domain with two hosted zones in AWS. (EXTPLESK-2359)

# 2.8.0

* [+] It is now possible to have white-label or vanity name server with Amazon Route 53. To configure them, users need to select the "Manage NS and SOA records" checkbox and then follow step 7 and further in the [Amazon Route 53 guide](https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/white-label-name-servers.html).

  The feature described above was [introduced by the extension’s user](https://github.com/plesk/ext-route53/pull/40). 
  We express our gratitude and welcome the contribution of Amazon Route 53 users into further development of the extension.

* [*] Added the warning message shown after users click the "Sync All Zones" button. The warning explains that Plesk will overwrite all DNS records in Route53 with those in Plesk and will remove those DNS records from Route53 that do not exist in Plesk.
* [-] The extension can now handle 2048-bit DKIM keys. (EXTPLESK-286)

# 2.7.3

* [-] CAA records in Plesk can now be synced with Amazon Route 53. (EXTPLESK-1611)
* [-] Installation of the extension in Plesk Obsidian no longer produces PHP error messages written to `/var/log/plesk/panel.log` in Plesk for Linux and `%plesk_dir%\admin\logs\php_error.log` in Plesk for Windows. (EXTPLESK-1143)

# 2.7.2

* [*] The extension now applies the TTL value of the DNS zone (instead of a default value) to all its DNS records.

# 2.7.1

* [*] The extension can now sync DNS zones with a large number of DNS records (more than 100). (EXTPLESK-393)

# 2.7.0

* [*] Translated the extension and its description into several new languages.
* [*] Changed to the three digit versioning scheme (x.y.z).

# 2.6

* [+] CLI improvements.

# 2.5

* [+] It is now possible to create restricted IAM-account for Route53 extension directly on the authorization form.
* [+] Added the ability to initialize the extension via API CLI.
* [*] `Remove All Zones` button now removes only the domains present in Plesk.
* [-] Zone update does not crash anymore when invalid characters are present (issue [#13](https://github.com/plesk/ext-route53/issues/13))
