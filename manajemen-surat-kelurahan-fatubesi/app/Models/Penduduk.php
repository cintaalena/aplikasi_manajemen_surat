<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    protected $table = 'penduduks';

    protected $fillable = [
        // Data Keluarga
        'kode_keluarga',
        'nama_kepala_keluarga',
        'alamat',
        'rt',
        'rw',
        'dusun',
        
        // Data Individu
        'no_urut',
        'nik',
        'nama',
        'jenis_kelamin',
        'hubungan',
        'tempat_lahir',
        'tanggal_lahir',
        'usia',
        'status_perkawinan',
        'agama',
        'golongan_darah',
        'kewarganegaraan',
        'etnis',
        'pendidikan',
        'pekerjaan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}
