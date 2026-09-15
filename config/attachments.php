<?php

declare(strict_types=1);

return [
    // 25 MB. Must stay ABOVE the web PHP limits so the originals actually
    // reach the server — ImageOptimizer shrinks them right after upload.
    'max_size_kb' => env('ATTACHMENT_MAX_SIZE_KB', 25600),

    'accepted_mimes' => [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
    ],

    'accepted_file_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ],
];
