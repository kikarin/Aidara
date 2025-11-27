<?php

namespace App\Http\Controllers;

use App\Repositories\ProfileRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ProfileController extends Controller
{
    use BaseTrait;

    private $repository;

    private $request;

    public function __construct(ProfileRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = $request;
        $this->initialize();
    }

    public function edit($id = '')
    {
        $data = $this->commonData + [];

        return view("$this->route.edit", $data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'email' => 'required|email|max:120',
            'no_hp' => 'required|max:20',
        ]);
        $this->repository->update(Auth::user()->id, $request->all());

        return redirect()->back()->with('success', trans('message.success_update'));
    }

    public function ganti_password()
    {
        $data = $this->commonData + [];

        return view("$this->route.ganti-password", $data);
    }

    public function ganti_password_action(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'new_password'              => 'required|string|min: 8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin',
            'new_password_confirmation' => 'required',
        ]);

        return $this->repository->gantiPassword(Auth::user()->id, $request->all());
    }

    public function change_role(Request $request)
    {
        $result = $this->repository->changeRole(Auth::user()->id, $request->role_id);
        if ($result['error'] == 0) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }

        return response()->json($result);
    }
}
