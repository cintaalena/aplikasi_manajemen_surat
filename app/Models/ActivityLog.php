<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Model ActivityLog — Jejak Audit (Audit Trail)
 *
 * Tabel ini bersifat append-only: tidak ada update, tidak ada delete.
 * Setiap perubahan pada data sensitif (User, Letter, Penduduk) otomatis tercatat di sini.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string $action          created|updated|deleted|restored
 * @property string $model_type      nama class model (Letter, Penduduk, User)
 * @property int $model_id
 * @property array|null $old_values  nilai lama (hanya saat updated)
 * @property array|null $new_values  nilai baru (hanya saat updated)
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class ActivityLog extends Model
{
    // Tidak ada updated_at — log immutable
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Catat satu entri audit log.
     * Dipanggil otomatis oleh trait Auditable, tidak perlu dipanggil manual.
     *
     * @param string $action     'created' | 'updated' | 'deleted' | 'restored'
     * @param Model  $model      Instance model yang berubah
     * @param array  $newValues  Nilai baru (untuk updated)
     * @param array  $oldValues  Nilai lama (untuk updated)
     */
    public static function record(string $action, Model $model, array $newValues = [], array $oldValues = []): void
    {
        try {
            $user = Auth::user();

            // Filter field yang tidak perlu dilog (timestamps, deleted_at)
            $exclude = ['updated_at', 'created_at', 'deleted_at', 'remember_token'];
            $newValues = array_diff_key($newValues, array_flip($exclude));
            $oldValues = array_diff_key($oldValues, array_flip($exclude));

            self::create([
                'user_id'    => $user?->id,
                'user_name'  => $user?->name ?? 'System',
                'action'     => $action,
                'model_type' => class_basename($model),
                'model_id'   => $model->getKey(),
                'old_values' => $oldValues ?: null,
                'new_values' => $newValues ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 512),
            ]);
        } catch (\Throwable $e) {
            // Jangan biarkan kegagalan audit log menghentikan operasi utama
            Log::error('ActivityLog::record gagal: ' . $e->getMessage(), [
                'action'     => $action,
                'model_type' => class_basename($model),
                'model_id'   => $model->getKey(),
            ]);
        }
    }

    /**
     * Relasi ke User (nullable karena bisa dari seeder/system)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
