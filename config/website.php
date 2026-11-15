<?php

return [
    'duplicate_window_minutes' => 15,
    'max_upload_kb' => 5120,

    'image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'artwork_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],

    'bag_categories' => ['shopping-bags', 'printed-bags', 'custom-bags'],
    'material_categories' => ['dana-granules'],

    'print_options' => [
        'none' => 'No print',
        'one_side' => 'One side',
        'both_sides' => 'Both sides',
        'logo' => 'Logo only',
    ],

    'enquiry_statuses' => [
        'new' => 'New',
        'in_review' => 'In review',
        'closed' => 'Closed',
    ],

    'map_hosts' => ['google.com', 'openstreetmap.org', 'apple.com'],
    'facebook_hosts' => ['facebook.com', 'fb.com'],
    'linkedin_hosts' => ['linkedin.com'],
];
