<?php

$EM_CONF[$_EXTKEY] = [
    'title'        => 'Site Master Data Extend',
    'description'  => 'Extends EXT:sitemasterdata with additional master data fields (example extension)',
    'category'     => 'misc',
    'author'       => 'porthd',
    'author_email' => '',
    'state'        => 'beta',
    'version'      => '0.0.1',
    'constraints'  => [
        'depends' => [
            'typo3'          => '14.3.0-14.99.99',
            'sitemasterdata' => '0.0.0-0.99.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
