<?php

namespace Database\Seeders;

use App\Models\CategoryIdentity;
use Illuminate\Database\Seeder;

class CategoryIdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listData = [
            [
                'id'          => 2,
                'name'        => 'Meta Admin Panel',
                'file'        => '' == null,
                'description' => 'Title, Description, Keyword',
                'sequence'    => 1,
            ],
            [
                'id'          => 3,
                'name'        => 'Footer',
                'file'        => null,
                'description' => null,
                'sequence'    => 2,
            ],
        ];
        foreach ($listData as $data) {
            CategoryIdentity::create($data);
        }
        $this->command->info('Category Identity table seeded!');
    }
}
