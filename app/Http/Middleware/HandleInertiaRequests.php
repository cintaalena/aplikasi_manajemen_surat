<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            return md5_file($manifestPath);
        }
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        return array_merge(parent::share($request), [
            'assetUrl' => rtrim(asset(''), '/'),
            'auth' => [
        'user' => $user ? [
            'id'      => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
            'jabatan' => $user->jabatan,
        ] : null,
            ],
            'flash' => [
                'success'              => fn () => $request->session()->get('success'),
                'error'               => fn () => $request->session()->get('error'),
                'credential'          => fn () => $request->session()->get('credential'),
                'credential_name'     => fn () => $request->session()->get('credential_name'),
                'credential_role'     => fn () => $request->session()->get('credential_role'),
                'credential_password' => fn () => $request->session()->get('credential_password'),
                'otp_sent'            => fn () => $request->session()->get('otp_sent'),
                'mail_warning'        => fn () => $request->session()->get('mail_warning'),
            ],
        ]);
    }
}
