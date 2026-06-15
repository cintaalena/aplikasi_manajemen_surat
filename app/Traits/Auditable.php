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
        static::created(function ($model) {
            ActivityLog::record('created', $model);
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $dirty);

            ActivityLog::record('updated', $model, $dirty, $original);
        });

        static::deleted(function ($model) {
            ActivityLog::record('deleted', $model);
        });

        if (method_exists(static::class, 'restoring')) {
            static::restored(function ($model) {
                ActivityLog::record('restored', $model);
            });
        }
    }
}
