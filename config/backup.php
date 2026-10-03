<?php

declare(strict_types=1);

return [
    // Outside the public folder and the private disk; never reachable by URL.
    'directory' => env('BACKUP_DIRECTORY', storage_path('app/backups')),

    // Plan §8.3: 14 days on the server (Hostinger's daily backups are the second copy).
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    // Path to mysqldump if it is not on the PATH (e.g. C:\xampp\mysql\bin\mysqldump.exe locally).
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
];
