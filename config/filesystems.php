<?php
return [
  'default' => env('FILESYSTEM_DISK', 'local'),
  'disks' => [
    'local' => [
      'driver' => 'local',
      'root' => storage_path('app'),
      'throw' => false,
    ],
    // future: s3/minio/azure via config + admin settings
  ],
];
