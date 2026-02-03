<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    protected $table = 'penduduks';

    protected $fillable = [
        'kode_keluarga',
        'nama_kepala_keluarga',
        'alamat',
        'rt',
        'rw',
        'nama_dusun',
        'bulan',
        'tahun',
        'nama_pengisi',
        'pekerjaan',
        'jabatan',
        'sumber_data',
    ];
}
