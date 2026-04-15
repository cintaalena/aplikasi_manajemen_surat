<?php

/**
 * Daftar kode indeks surat kelurahan — sesuai klasifikasi arsip pemerintah daerah.
 * Hanya mencakup bidang Kependudukan dan Catatan Sipil (400.12) yang relevan
 * untuk Kelurahan Fatubesi.
 */

return [
    [
        'key'   => 'kependudukan_umum',
        'label' => 'Kebijakan Kependudukan',
        'items' => [
            ['code' => '400.12.1', 'name' => 'Kebijakan di Bidang Kependudukan dan Catatan Sipil'],
        ],
    ],
    [
        'key'   => 'pendaftaran_penduduk',
        'label' => 'Pendaftaran Penduduk (400.12.2)',
        'items' => [
            ['code' => '400.12.2',   'name' => 'Pendaftaran Penduduk (Umum)'],
            ['code' => '400.12.2.1', 'name' => 'Identitas Penduduk'],
            ['code' => '400.12.2.2', 'name' => 'Pindah Datang Penduduk Dalam Wilayah NKRI'],
            ['code' => '400.12.2.3', 'name' => 'Pindah Datang Penduduk Antar Negara'],
            ['code' => '400.12.2.4', 'name' => 'Pendataan Penduduk Rentan'],
            ['code' => '400.12.2.5', 'name' => 'Monitoring Evaluasi dan Dokumentasi'],
        ],
    ],
    [
        'key'   => 'pencatatan_sipil',
        'label' => 'Pencatatan Sipil (400.12.3)',
        'items' => [
            ['code' => '400.12.3',   'name' => 'Pencatatan Sipil (Umum)'],
            ['code' => '400.12.3.1', 'name' => 'Kelahiran dan Kematian'],
            ['code' => '400.12.3.2', 'name' => 'Perkawinan dan Perceraian'],
            ['code' => '400.12.3.3', 'name' => 'Pengangkatan, Pengakuan dan Pengesahan Anak serta Perubahan'],
            ['code' => '400.12.3.4', 'name' => 'Pencatatan Kewarganegaraan'],
            ['code' => '400.12.3.5', 'name' => 'Monitoring Evaluasi dan Dokumentasi'],
        ],
    ],
    [
        'key'   => 'informasi_kependudukan',
        'label' => 'Pengelolaan Informasi Administrasi Kependudukan (400.12.4)',
        'items' => [
            ['code' => '400.12.4',   'name' => 'Pengelolaan Informasi Administrasi Kependudukan (Umum)'],
            ['code' => '400.12.4.1', 'name' => 'Sistem Informasi Administrasi Kependudukan'],
            ['code' => '400.12.4.2', 'name' => 'Kelembagaan Informasi Administrasi Kependudukan'],
            ['code' => '400.12.4.3', 'name' => 'Pengelolaan Data Administrasi Kependudukan'],
        ],
    ],
];
