<?php

namespace App\Repositories;

use App\Notifications\RegisterNotification;

class SingupRepository
{
    protected $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    public function signUp($data)
    {

        $data = array_merge($data, [
            'role_id'       => null,
            'name'          => null,
            'email'         => null,
            'password'      => null,
            'is_verifikasi' => 1,
        ]);
        if ($data['is_verifikasi'] == 0) {
            $data['verification_token'] = sha1(time());
        }

        $fieldsToUnset = ['persetujuan', 'jenis', 'password_confirmation'];
        foreach ($fieldsToUnset as $field) {
            unset($data[$field]);
        }
        $user = $this->usersRepository->create($data);
        if ($data['is_verifikasi'] == 0) {
            $user->notify(new RegisterNotification());
            session(['verificationEmail' => $data['email']]);
        }

        return $user;
    }
}
