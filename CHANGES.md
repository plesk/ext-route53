# 2.7.1

* [*] The extension can now sync DNS zones with a large number of DNS records (more than 100).

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