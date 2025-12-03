<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function index()
    {
        $data = [
            'titlePage'             => 'Login',
            'generateCaptcha_Login' => generateCaptcha('captcha_login'),
        ];

        return view('auth.login', $data);
    }

    public function action(Request $request)
    {
        $request->validate([
            'email'    => 'required',
            'password' => 'required|string',
            'captcha'  => 'required|numeric',
        ]);
        $remember    = $request->has('remember') ? true : false;
        $credentials = $request->only('email', 'password');

        if (! verifyCaptcha('captcha_login', $request->captcha)) {
            return redirect('login')->withError('Captcha is not valid!');
        }

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
<<<<<<< HEAD
            if ($user->verification_token != null) {
                Auth::logout();

                return redirect('login')->withError('Account has not been verified!');
            }
=======
            
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
            if ($user->is_active == 0) {
                Auth::logout();

                return redirect('login')->withError('Your account is not active!');
            }
<<<<<<< HEAD
=======
            
            // Cek apakah email sudah verified
            // Jika belum verified, redirect ke halaman OTP verification (tidak logout)
            if (!$user->email_verified_at) {
                activity()->event('Login')->performedOn(User::find($user->id))->log('Auth');
                User::where('id', $user->id)->update(['last_login' => now()]);
                
                // Jika belum ada OTP, kirim OTP baru
                if (!$user->email_otp || !$user->email_otp_expires_at) {
                    $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    
                    $user->update([
                        'email_otp' => bcrypt($otpCode),
                        'email_otp_expires_at' => now()->addMinutes(10),
                    ]);

                    // Kirim email OTP
                    $user->notify(new \App\Notifications\EmailOtpNotification($otpCode));
                    
                    // Simpan waktu terakhir OTP dikirim untuk cooldown
                    $request->session()->put('otp_last_sent', now());
                }
                
                return redirect()->route('email.otp.verify')
                    ->with('warning', 'Email Anda belum diverifikasi. Silakan masukkan kode OTP yang telah dikirim ke email Anda.');
            }
            
            if ($user->verification_token != null) {
                Auth::logout();

                return redirect('login')->withError('Account has not been verified!');
            }
            
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
            activity()->event('Login')->performedOn(User::find($user->id))->log('Auth');
            User::where('id', $user->id)->update(['last_login' => now()]);
            // return redirect('dashboard')->withSuccess("Login Successful");
            $init_page_login = ($user->role->init_page_login != '') ? $user->role->init_page_login : 'dashboard';

            return redirect($init_page_login)->withSuccess('Login Successful');
        }

        return redirect('login')->withError('Login Failed!');
    }

    public function logout(Request $request, $is_front = 0)
    {
        if (Session::get('is_login_as')) {
            Auth::loginUsingId(Session::get('users_id_lama'), false);
            Session::forget('is_login_as');
            Session::forget('users_id_lama');
            $user = Auth::user();
            if ($is_front == 1) {
                return redirect()->route('front.home.index', ['is_refresh' => 1]);
            } else {
                $init_page_login = ($user->role->init_page_login != '') ? $user->role->init_page_login : 'dashboard';

                return redirect($init_page_login);
            }
        } else {
            activity()->event('Logout')->performedOn(User::find(Auth::user()->id))->log('Auth');
            // $request->session()->flush();
            Auth::logout();
            // Menghapus session yang ada
            $request->session()->invalidate();

            // Tidak menghapus cookie "remember me"
            $request->session()->regenerate(false);
            if ($is_front == 1) {
                return redirect()->route('front.home.index', ['is_refresh' => 1]);
            } else {
                return redirect()->route('login');
            }
        }
    }
}
