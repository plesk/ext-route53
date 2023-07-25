<?php
// Copyright 1999-2023. Plesk International GmbH. All rights reserved.

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'PleskRoute53',
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock|Dockerfile/')
            ->exclude([
                'resources',
                'views',
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('src/plib'),
    ],
    'patchers' => [
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/Sdk.php$#", $filePath)
                || preg_match("#/aws-sdk-php/src/AwsClient.php$#", $filePath)
                || preg_match("#/aws-sdk-php/src/MultiRegionClient.php$#", $filePath)
            ) {
                return preg_replace("#\"Aws\\\\#", "\"$prefix\\\\\\\\Aws\\", $contents);
            }
            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/Signature/SignatureV4.php$#", $filePath)) {
                return preg_replace("#ISO8601_BASIC = \'[a-zA-Z0-9\\\]{1,}#", "ISO8601_BASIC = 'Ymd\THis\Z", $contents);
            }
            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/Endpoint/UseFipsEndpoint/Configuration.php$#", $filePath)
                || preg_match("#/aws-sdk-php/src/S3/UseArnRegion/Configuration.php$#", $filePath)
                || preg_match("#/aws-sdk-php/src/Endpoint/UseDualstackEndpoint/Configuration.php$#", $filePath)) {
                return preg_replace("#Aws\\\\boolean_value#", "\\$prefix\\\\Aws\\\\boolean_value", $contents);
            }
            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/functions.php$#", $filePath)) {
                return preg_replace("#GuzzleHttp\\\\\\\\ClientInterface::#", "$prefix\\\\\\\\GuzzleHttp\\\\\\\\ClientInterface::", $contents);
            }
            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/EndpointV2/Ruleset/RulesetStandardLibrary.php$#", $filePath)) {
                return preg_replace("#\'Aws\\\\\\\\EndpointV2#", "'$prefix\\\\\\\\Aws\\\\\\\\EndpointV2", $contents);
            }
            return $contents;
        },
    ],
    'exclude-namespaces' => [
        '~^Plesk~',
        'Psr',
        '~^$~',
    ],
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
];
