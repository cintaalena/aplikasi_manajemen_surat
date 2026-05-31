<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\AsEncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penduduk extends Model
{
    use SoftDeletes, Auditable;

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
        'status_kehidupan',
    ];

    // Field ini tidak akan pernah muncul dalam JSON response otomatis
    // NIK adalah data sensitif PII (Personally Identifiable Information)
    protected $hidden = [
        'deleted_at',
    
        'nik_hash',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'deleted_at'    => 'datetime',
    ];
}
