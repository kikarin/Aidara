<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignupRequest;
use App\Repositories\SingupRepository;
use App\Repositories\UsersRoleRepository;

class SignupController extends Controller
{
    private $repository;

    private $usersRoleRepository;

    public function __construct(SingupRepository $repository, UsersRoleRepository $usersRoleRepository)
    {
        $this->repository          = $repository;
        $this->usersRoleRepository = $usersRoleRepository;
    }

    public function index()
    {
        $data = [
            'titlePage' => 'Sign Up',
            'jenis'     => request()->input('jenis', null),
        ];

        return view('signup.index', $data);
    }

    public function action(SignupRequest $request)
    {
        $data = $request->validated();
        $user = $this->repository->signUp($data);
        if ($user->is_verifikasi == 0) {
            return redirect('login')->with('success_register_and_verify', trans('message.success_register_and_verify'));
        } else {
            return redirect('login')->with('success', trans('message.success_register'));
        }
    }
}
