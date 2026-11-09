<?php

// Поверх настроек Livewire по умолчанию (слияние по верхним ключам — раздел задаётся целиком).
return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        // Фото до 15 МБ (как ImageUploadRule::MAX_KB); по умолчанию Livewire пускает только 12 МБ.
        'rules' => ['required', 'file', 'max:15360'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
