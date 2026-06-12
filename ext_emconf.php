<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'File No-Index',
    'description' => 'Exclude single files/images from search engines (Google Image Search) via a dynamically generated robots.txt - toggled per file in the file list.',
    'category' => 'fe',
    'author' => 'Phillip Baumgärtner',
    'author_email' => 'baumgaertner@bm1.de',
    'author_company' => 'Baumgärtner Marketing GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'php' => '8.2.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
