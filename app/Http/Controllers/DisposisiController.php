<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterDisposition;
use App\Models\LetterNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisposisiController extends Controller
{
    /**
     * Lurah mendisposisikan surat ke staff tertentu.
     */
    public function store(Request $request, Letter $letter)
    {
        $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'catatan'    => ['nullable', 'string', 'max:1000'],
        ]);

        $toUser = User::where('id', $request->to_user_id)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->firstOrFail();

        // Buat record disposisi
        LetterDisposition::create([
            'letter_id'    => $letter->id,
            'from_user_id' => $request->user()->id,
            'to_user_id'   => $toUser->id,
            'catatan'      => $request->catatan,
            'status'       => 'pending',
        ]);

        // Kirim notifikasi ke staff yang dituju
        LetterNotification::create([
            'user_id'   => $toUser->id,
            'letter_id' => $letter->id,
            'message'   => "Surat \"{$letter->title}\" (No. {$letter->no_surat}) telah didisposisikan kepada Anda oleh " . $request->user()->name . ".",
            'is_read'   => false,
        ]);

        return back()->with('success', "Surat berhasil didisposisikan ke {$toUser->name}.");
    }

    /**
     * Halaman Disposisi Tugas — staff melihat daftar tugas disposisi.
     */
    public function index(Request $request)
    {
        $dispositions = LetterDisposition::with([
                'letter:id,no_surat,title,printed_at,is_manual,template_slug',
                'letter.documents:id,letter_id,doc_key,doc_label,original_name,mime_type,file_size,file_path',
                'fromUser:id,name,jabatan',
            ])
            ->where('to_user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('DisposisiTugas/Index', [
            'dispositions' => $dispositions,
        ]);
    }

    /**
     * Staff menandai disposisi sebagai selesai.
     */
    public function markSelesai(Request $request, LetterDisposition $disposisi)
    {
        if ($disposisi->to_user_id !== $request->user()->id) {
            abort(403);
        }

        $disposisi->update(['status' => 'selesai']);

        return back()->with('success', 'Tugas disposisi ditandai sebagai selesai.');
    }

    /**
     * Daftar staff aktif untuk dropdown disposisi (dipakai oleh lurah).
     */
    public function staffList()
    {
        $staff = User::where('role', 'staff')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan']);

        return response()->json($staff);
    }
}
