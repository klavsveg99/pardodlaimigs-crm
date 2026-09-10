<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRM Backups
    |--------------------------------------------------------------------------
    |
    | Where `pdc:backup-database` (scheduled daily at 00:00 Europe/Riga) writes
    | its full-database copies and how many to keep. On production (Hostinger)
    | the directory lives OUTSIDE public_html; locally it falls back to
    | storage/app/backups. Manual / emergency copies are never rotated away.
    |
    */

    'dir' => env('BACKUP_DIR', storage_path('app/backups')),

    'keep' => (int) env('BACKUP_KEEP', 3),

];
