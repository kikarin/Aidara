<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function verify(Request $request, $token)
    {
        $user = User::where('verification_token', $token)->firstOrFail();
        $user->update([
            'verification_token' => null,
            'email_verified_at'  => now(),
            'is_verifikasi'      => 1,
        ]);
        Auth::login($user);
        $init_page_login = ($user->role->init_page_login != '') ? $user->role->init_page_login : 'dashboard';

        return redirect($init_page_login)->withSuccess('Login Successful');
    }

    public function resend(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $user->sendEmailVerificationNotification();

        return back()->with('success', trans('message.success_resend'));
    }
}
