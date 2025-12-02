<?php

namespace App\Repositories;

use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Hash;

class ProfileRepository
{
    use RepositoryTrait;

    private $usersRepository;

    private $roleRepository;

    public function __construct(UsersRepository $usersRepository, RoleRepository $roleRepository)
    {
        $this->usersRepository = $usersRepository;
        $this->roleRepository  = $roleRepository;
    }

    public function update($userId, $data)
    {
        $user = $this->usersRepository->getById($userId);
        if (! empty($data['file'])) {
            $uploadFile   = $this->uploadFileCustom($data['file'], 'users');
            $data['file'] = @$uploadFile['filename'];
        }
        $file_lama = $user->file;
        $user->update($data);
        activity()->event('Update Profile')->performedOn($user)->log('Profile');
        if (! empty($data['file'])) {
            $this->deleteFileCustom($file_lama, 'users');
        }

        return $user;
    }

    public function gantiPassword($userId, $data)
    {
        $user = $this->usersRepository->getById($userId);
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok']);
        }
        $user->password = Hash::make($data['new_password']);
        $user->save();
        activity()->event('Ganti Password')->performedOn($user)->log('Profile');

        return redirect()->back()->with('success', 'Successfully change password');
    }

    public function changeRole($userId, $role_id)
    {
        $user          = $this->usersRepository->getById($userId);
        $role_id_array = $user->users_role->pluck('role_id')->toArray();
        if (! in_array($role_id, $role_id_array)) {
            return [
                'error'   => 1,
                'message' => 'Role tidak terdaftar pada user',
            ];
        }
        activity()->disableLogging();
        $properties['old'] = $this->roleRepository->getInstanceModel()::find($user->current_role_id);
        $user->update(['current_role_id' => $role_id]);
        $user->syncRoles([(int) $role_id]);
        $properties['attributes'] = $this->roleRepository->getInstanceModel()::find($role_id);
        activity()->enableLogging();
        activity()->event('Change Role')->performedOn($user)->withProperties($properties)->log('Profile');

        return [
            'error'           => 0,
            'message'         => 'Success change role',
            'init_page_login' => $properties['attributes']->init_page_login,
        ];
    }
}
