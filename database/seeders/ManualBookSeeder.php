<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\UsersMenu;
use App\Repositories\UsersMenuRepository;
use Illuminate\Database\Seeder;

class ManualBookSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::query()->where('name', 'Dashboard Show')->first();

        UsersMenu::query()->updateOrCreate(
            ['kode' => 'MANUAL-BOOK'],
            [
                'nama'          => 'Manual Book',
                'url'           => '/manual-book',
                'icon'          => 'BookOpen',
                'rel'           => 0,
                'urutan'        => 2,
                'permission_id' => $permission?->id,
            ],
        );

        app(UsersMenuRepository::class)->invalidateMenusCache();
    }
}
