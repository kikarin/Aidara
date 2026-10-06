<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\CategoryPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UsersMenu;
use App\Repositories\UsersMenuRepository;
use Illuminate\Database\Seeder;

class WorldCupSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $permission = $this->seedPermission();
        $this->seedMenu($permission);
        $this->assignPermissionToSuperAdmin($permission);
    }

    private function seedSettings(): void
    {
        $defaults = [
            'worldcup.enabled' => '0',
            'worldcup.preview_count' => '7',
            'worldcup.show_on_landing' => '1',
            'worldcup.section_title' => 'Piala Dunia 2026',
        ];

        foreach ($defaults as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    private function seedPermission(): Permission
    {
        $category = CategoryPermission::query()->firstOrCreate([
            'name' => 'Pengaturan World Cup',
        ]);

        return Permission::query()->updateOrCreate(
            ['name' => 'Pengaturan World Cup Edit'],
            ['category_permission_id' => $category->id],
        );
    }

    private function seedMenu(Permission $permission): void
    {
        UsersMenu::query()->updateOrCreate(
            ['kode' => 'SETTINGS-WORLDCUP'],
            [
                'nama' => 'Live Score Piala Dunia',
                'url' => '/dashboard/settings/worldcup',
                'icon' => 'Trophy',
                'rel' => 0,
                'urutan' => 104,
                'permission_id' => $permission->id,
            ],
        );
    }

    private function assignPermissionToSuperAdmin(Permission $permission): void
    {
        $adminRole = Role::query()->find(1);

        if ($adminRole === null) {
            return;
        }

        if (! $adminRole->hasPermissionTo($permission->name)) {
            $adminRole->givePermissionTo($permission);
        }

        app(UsersMenuRepository::class)->invalidateMenusCache();
    }
}
