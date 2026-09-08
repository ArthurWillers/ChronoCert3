<?php

return [
    'documents' => [
        'accepts_multiple' => true,
        'required' => false,
        'max_file_size_bytes' => 10 * 1024 * 1024,
        'mime_types' => [
            'application/pdf' => 'PDF',
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/webp' => 'WebP',
            'image/bmp' => 'BMP',
        ],
    ],
];
