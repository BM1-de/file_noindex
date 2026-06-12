<?php

declare(strict_types=1);

use Bm1\FileNoindex\Middleware\RobotsTxtMiddleware;

return [
    'frontend' => [
        'bm1/file-noindex/robots-txt' => [
            'target' => RobotsTxtMiddleware::class,
            // Needs the resolved site, must answer before a configured
            // robots.txt staticText route would
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
    ],
];
