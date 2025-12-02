<?php

namespace App\Http\Controllers;

use App\Jobs\SendPasswordResetEmail;
use App\Repositories\UsersRepository;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    private $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    public function index()
    {
        $data = [
            'titlePage' => 'Forgot Password',
        ];

        return view('auth.forgot-password', $data);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $lastResetTime = Cache::get("password_reset_$email");
        if ($lastResetTime) {
            $now            = Carbon::now();
            $timeDifference = $now->diffInMinutes($lastResetTime);
            if ($timeDifference < 5) {
                return back()->withErrors('Harap tunggu sebelum mencoba lagi');
            }
        }
        Cache::put("password_reset_$email", Carbon::now(), 5); // Cache for 5 minutes

        $user = $this->usersRepository->getByEmail($email);
        if (! $user) {
            return back()->withSuccess('We have emailed your password reset link.');
        }

        // Dispatch job to send password reset email
        SendPasswordResetEmail::dispatch($email);

        return back()->with('success', 'We have emailed your password reset link.');
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in: password,123456,admin',
            'password_confirmation' => 'required',
        ], [
            'password.required'              => 'Password harus diisi.',
            'password.min'                   => 'Password minimal harus terdiri dari 8 karakter.',
            'password.regex'                 => 'Password harus mengandung setidaknya satu huruf kecil, satu huruf besar, dan satu angka.',
            'password.confirmed'             => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password harus diisi.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        echo $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
