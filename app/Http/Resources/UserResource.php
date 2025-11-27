<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'no_hp'         => $this->no_hp,
            'tanggal_lahir' => $this->tanggal_lahir,
            'is_active'     => $this->is_active,
            'is_verifikasi' => $this->is_verifikasi,
            'current_role'  => $this->whenLoaded('role', function () {
                return [
                    'id'               => $this->role->id,
                    'name'             => $this->role->name,
                    'init_page_login'  => $this->role->init_page_login,
                    'bg'               => $this->role->bg,
                    'is_allow_login'   => $this->role->is_allow_login,
                    'is_vertical_menu' => $this->role->is_vertical_menu,
                ];
            }),
            'all_roles' => $this->whenLoaded('users_role', function () {
                return $this->users_role->map(function ($userRole) {
                    return [
                        'id'          => $userRole->role->id,
                        'name'        => $userRole->role->name,
                        'description' => $userRole->role->description ?? null,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_login' => $this->last_login,
        ];
    }
}
