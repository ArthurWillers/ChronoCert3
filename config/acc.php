<?php

return [
    'documents' => [
        'accepts_multiple' => false,
        'required' => true,
        'max_file_size_bytes' => 10 * 1024 * 1024,
        'accepted_file_types' => [
            'application/pdf' => ['label' => 'PDF', 'extensions' => ['pdf']],
            'image/jpeg' => ['label' => 'JPEG', 'extensions' => ['jpg', 'jpeg']],
            'image/png' => ['label' => 'PNG', 'extensions' => ['png']],
            'image/webp' => ['label' => 'WebP', 'extensions' => ['webp']],
        ],
    ],
];
