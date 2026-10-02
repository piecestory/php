<?php

declare(strict_types=1);

return [
    'store' => [
        'title' => 'Store',
        'intro' => 'Carefully chosen antiques and rare pieces, each with a story.',
    ],
    'search' => [
        'title' => 'Search',
        'results_for' => 'Results for “:query”',
        'placeholder' => 'Search for a piece…',
        'submit' => 'Search',
        'label' => 'Search the store',
    ],
    'collections' => [
        'title' => 'Collections',
        'intro' => 'Curated sets of pieces that share a spirit and an era.',
        'pieces' => '{0} No pieces|{1} 1 piece|[2,*] :count pieces',
        'empty' => 'No collections have been published yet.',
    ],
    'results' => '{0} No pieces|{1} 1 piece|[2,*] :count pieces',
    'empty' => [
        'title' => 'No matching pieces',
        'text' => 'Try removing some filters or searching for a different word.',
        'catalog' => 'New pieces are arriving soon.',
        'reset' => 'Clear filters',
    ],
    'sort' => [
        'label' => 'Sort by',
        'relevance' => 'Most relevant',
        'newest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
    ],
    'filters' => [
        'title' => 'Refine results',
        'open' => 'Filters',
        'apply' => 'Show results',
        'reset' => 'Clear all',
        'close' => 'Close filters',
        'categories' => 'Categories',
        'all' => 'All pieces',
        'price' => 'Price (SAR)',
        'price_min' => 'From',
        'price_max' => 'To',
        'era' => 'Era',
        'origin' => 'Origin',
        'material' => 'Material',
        'condition' => 'Condition',
        'available' => 'Available only',
        'rare' => 'Rare pieces only',
    ],
    'condition' => [
        'excellent' => 'Excellent',
        'very_good' => 'Very good',
        'good' => 'Good',
        'restored' => 'Restored',
        'as_found' => 'As found',
    ],
    'pagination' => [
        'label' => 'Pagination',
        'previous' => 'Previous',
        'next' => 'Next',
        'page' => 'Page :page',
    ],
];
