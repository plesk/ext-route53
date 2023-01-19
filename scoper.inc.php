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
                return preg_replace("#\"Aws\\\\#", "\"PleskRoute53\\\\\\\\Aws\\", $contents);
            }
            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (preg_match("#/aws-sdk-php/src/Signature/SignatureV4.php$#", $filePath)) {
                return preg_replace("#ISO8601_BASIC = \'[a-zA-Z0-9\\\]{1,}#", "ISO8601_BASIC = 'Ymd\THis\Z", $contents);
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