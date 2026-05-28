<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::where('role', '!=', 'admin')
            ->orderBy('jabatan')
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'jabatan', 'role', 'is_active', 'email', 'credential_issued_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:200'],
            'nip'      => ['nullable', 'string', 'max:50'],
            'email'    => ['required', 'email', 'max:200', 'unique:users,email'],
            'jabatan'  => ['required', 'string', 'max:100'],
            'role'     => ['required', 'in:lurah,staff'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        // Credential otomatis: A-001 untuk lurah, B-001 untuk staff
        $credentialCode = $data['role'] === 'lurah' ? 'A-001' : 'B-001';

        User::create([
            'name'                 => strtoupper($data['name']),
            'nip'                  => $data['nip'] ?? null,
            'email'                => $data['email'],
            'jabatan'              => $data['jabatan'],
            'role'                 => $data['role'],
            'password'             => Hash::make($data['password']),
            'credential_code_hash' => Hash::make($credentialCode),
            'credential_issued_at' => now(),
            'is_active'            => true,
            'email_verified_at'    => now(),
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:200'],
            'nip'     => ['nullable', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:100'],
            'role'    => ['required', 'in:lurah,staff'],
        ]);

        $user->update([
            'name'    => strtoupper($data['name']),
            'nip'     => $data['nip'] ?? null,
            'jabatan' => $data['jabatan'],
            'role'    => $data['role'],
        ]);

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Pengguna berhasil {$status}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password berhasil direset.');
    }
}
