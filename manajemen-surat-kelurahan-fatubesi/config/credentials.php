<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jabatan Credential Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping antara jabatan dengan credential code yang tetap.
    | Setiap jabatan memiliki satu credential yang sama untuk semua user
    | dengan jabatan tersebut.
    |
    */

    'jabatan_credentials' => [
        'lurah' => 'A-001',
        'sekretaris' => 'A-002',
        'kasie pelayanan masyarakat' => 'A-003',
        'kasie pem dan trantib umum' => 'A-004',
        'pengelola pemberdayaan masyarakat dan kelembagaan' => 'A-005',
        'pengadministrasian umum' => 'A-006',
        'pppk' => 'A-007',
        'ptt' => 'A-008',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Credential
    |--------------------------------------------------------------------------
    |
    | Credential default untuk jabatan yang tidak terdaftar di mapping
    |
    */
    'default_credential' => 'A-999',
];
