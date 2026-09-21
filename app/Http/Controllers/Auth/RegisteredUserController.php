<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Cek apakah email sudah ada (termasuk soft delete)
        $existingUser = User::withTrashed()->where('email', $request->email)->first();
        
        // Jika user sudah ada dan tidak di-soft delete, validasi unique akan gagal
        $emailRule = ['required', 'string', 'lowercase', 'email', 'max:255'];
        
        if ($existingUser && !$existingUser->trashed()) {
            // User aktif, gunakan unique validation
            $emailRule[] = Rule::unique('users', 'email');
        }
        
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => $emailRule,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Jika user sudah ada dan di-soft delete, restore
        if ($existingUser && $existingUser->trashed()) {
            $existingUser->restore();
            $user = $existingUser;
            $user->update([
                'name'     => $request->name,
                'password' => Hash::make($request->password),
            ]);
        } else {
            // Buat user baru
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
