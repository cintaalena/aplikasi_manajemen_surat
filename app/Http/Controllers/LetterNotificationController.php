<?php

namespace App\Http\Controllers;

use App\Models\LetterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LetterNotificationController extends Controller
{
    /**
     * Ambil notifikasi untuk lurah yang sedang login.
     * Mengembalikan 20 notifikasi terbaru, jumlah belum dibaca,
     * dan flag has_sound_ping (untuk tes bunyi tanpa DB record).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Konsumsi sound-ping dari cache secara atomik
        $hasSoundPing = (bool) Cache::pull("notif_sound_ping_{$user->id}", false);

        $notifications = LetterNotification::with('letter:id,no_surat,title,printed_at')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'letter_id', 'message', 'is_read', 'created_at']);

        $unreadCount = LetterNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications'  => $notifications,
            'unread_count'   => $unreadCount,
            'has_sound_ping' => $hasSoundPing,
        ]);
    }

    /**
     * Tandai semua notifikasi milik lurah yang login sebagai sudah dibaca.
     */
    public function markAllRead(Request $request)
    {
        LetterNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markRead(Request $request, LetterNotification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Server-Sent Events — pola "one-shot": cek DB sekali, kirim event, tutup koneksi.
     * Browser (EventSource) reconnect otomatis setiap `retry` ms.
     *
     * Kenapa one-shot, bukan loop selamanya?
     * XAMPP/Apache prefork: satu PHP worker = satu request. Loop tak berujung
     * memblokir worker tersebut selamanya, menghambat request lain dari browser
     * yang sama (navigasi, Inertia, dsb). One-shot membebaskan worker setelah
     * setiap respons sehingga browser bisa bernavigasi normal.
     */
    public function stream(Request $request)
    {
        $user   = $request->user();
        $lastId = (int) $request->header('Last-Event-ID', 0);

        // Lepas session file lock SEKARANG — sebelum query DB apapun.
        // Selama session masih terkunci, setiap request lain dari browser yang sama
        // (upload, navigasi, dsb) mengantri. Dengan melepas di sini, upload 300 KB
        // tidak perlu menunggu SSE menyelesaikan query-nya.
        session()->save();
        session_write_close();

        // Titik awal: pakai Last-Event-ID saat reconnect, atau ID notif terbesar saat ini
        $since = $lastId > 0
            ? $lastId
            : (LetterNotification::where('user_id', $user->id)->max('id') ?? 0);

        // Kumpulkan data SEBELUM streaming (DB query di luar closure)
        $newNotifs = LetterNotification::with('letter:id,no_surat,title,printed_at')
            ->where('user_id', $user->id)
            ->where('id', '>', $since)
            ->orderBy('id')
            ->get(['id', 'letter_id', 'message', 'is_read', 'created_at']);

        // Cache::pull = get + delete atomik (tidak perlu forget terpisah)
        $pingKey = "notif_sound_ping_{$user->id}";
        $hasPing = (bool) Cache::pull($pingKey, false);

        $unreadCount = $newNotifs->isNotEmpty()
            ? LetterNotification::where('user_id', $user->id)->where('is_read', false)->count()
            : 0;

        return response()->stream(function () use ($newNotifs, $hasPing, $unreadCount): void {
            // Bersihkan output buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Sound-ping (testing) — bunyi saja, tidak masuk panel notifikasi
            if ($hasPing) {
                echo "event: sound\n";
                echo 'data: ' . json_encode(['ts' => time()]) . "\n\n";
            }

            // Notifikasi arsip baru
            foreach ($newNotifs as $notif) {
                echo "id: {$notif->id}\n";
                echo "event: notification\n";
                echo 'data: ' . json_encode([
                    'notification' => $notif,
                    'unread_count' => $unreadCount,
                ]) . "\n\n";
            }

            // Instruksi reconnect: browser sambung lagi dalam 3 detik
            echo "retry: 3000\n";
            echo ": ok\n\n";

            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=UTF-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'close',
        ]);
    }
}
