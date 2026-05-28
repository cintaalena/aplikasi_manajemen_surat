<?php

namespace App\Traits;

use App\Models\ActivityLog;

/**
 * Trait Auditable — Jejak Audit Otomatis
 *
 * Tambahkan trait ini ke model manapun yang ingin diaudit.
 * Secara otomatis akan mencatat ke tabel activity_logs setiap kali:
 *   - Record dibuat (created)
 *   - Record diperbarui (updated) — termasuk field mana yang berubah
 *   - Record dihapus soft-delete (deleted)
 *   - Record dipulihkan dari soft-delete (restored)
 *
 * Cara pakai:
 *   class Letter extends Model {
 *       use SoftDeletes, Auditable;
 *   }
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        // Catat saat data baru dibuat
        static::created(function ($model) {
            ActivityLog::record('created', $model);
        });

        // Catat saat data diperbarui, beserta field yang berubah
        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return; // tidak ada yang benar-benar berubah
            }

            // Ambil nilai lama hanya untuk field yang berubah
            $original = array_intersect_key($model->getOriginal(), $dirty);

            ActivityLog::record('updated', $model, $dirty, $original);
        });

        // Catat saat data dihapus (soft delete maupun permanent)
        static::deleted(function ($model) {
            ActivityLog::record('deleted', $model);
        });

        // Catat saat data dipulihkan dari soft delete (jika model pakai SoftDeletes)
        if (method_exists(static::class, 'restoring')) {
            static::restored(function ($model) {
                ActivityLog::record('restored', $model);
            });
        }
    }
}
