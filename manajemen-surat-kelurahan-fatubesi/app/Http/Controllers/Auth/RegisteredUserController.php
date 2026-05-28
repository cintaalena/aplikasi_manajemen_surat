<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        'jabatan' => ['required', 'string', 'max:50'],
    ]);

    $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'jabatan' => $request->jabatan,
        ]);

        return $user;
    });

    \Illuminate\Support\Facades\Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('dashboard');
}
}
