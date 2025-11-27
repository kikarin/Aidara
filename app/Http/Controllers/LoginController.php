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
            if ($user->verification_token != null) {
                Auth::logout();

                return redirect('login')->withError('Account has not been verified!');
            }
            if ($user->is_active == 0) {
                Auth::logout();

                return redirect('login')->withError('Your account is not active!');
            }
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
