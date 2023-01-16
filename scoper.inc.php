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
    'exclude-namespaces' => [
        '~^Plesk~',
        'Psr',
        '~^$~',
    ],
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
];