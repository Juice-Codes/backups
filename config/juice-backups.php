<?php

return [

    /*
     * Prefix of backup file filename.
     *
     * Do not contain "-" at the end.
     */

    'name' => env('APP_NAME', 'juice'),

    /*
     * Target directory that stores the backup files.
     *
     * Please ensure this directory will not contain
     * other files, or it will result in unexpected
     * behavior.
     */

    'destination' => storage_path('jb-backups'),

    /*
     * Files and directories that will be backup.
     */

    'includes' => [
        base_path(),
    ],

    'excludes' => [
        base_path('storage'),
        base_path('vendor'),
    ],

];
