<?php

namespace Database\Seeders;

use App\Models\Identity;
use Illuminate\Database\Seeder;

class IdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listData = [
            [
                'id'                   => 1,
                'kode'                 => 'title',
                'name'                 => 'Title',
                'type'                 => 'Text',
                'value'                => 'NEW CORE',
                'category_identity_id' => 2,
                'type_file'            => null,
                'sequence'             => 1,
            ],
            [
                'id'                   => 3,
                'kode'                 => 'keyword',
                'name'                 => 'Keyword',
                'type'                 => 'Textarea',
                'value'                => null,
                'category_identity_id' => 2,
                'type_file'            => null,
                'sequence'             => 3,
            ],
            [
                'id'                   => 4,
                'kode'                 => 'logo',
                'name'                 => 'Logo',
                'type'                 => 'File',
                'value'                => '20240322060448-Screenshot_2024-03-22_at_06.01.16-removebg-preview.png',
                'category_identity_id' => 2,
                'type_file'            => 'png',
                'sequence'             => 4,
            ],
            [
                'id'                   => 5,
                'kode'                 => 'description',
                'name'                 => 'Description',
                'type'                 => 'Textarea',
                'value'                => 'NEW CORE Panel',
                'category_identity_id' => 2,
                'type_file'            => null,
                'sequence'             => 2,
            ],
            [
                'id'                   => 6,
                'kode'                 => 'ico',
                'name'                 => 'Ico',
                'type'                 => 'File',
                'value'                => '20240322060448-Screenshot_2024-03-22_at_06.01.16-removebg-preview.png',
                'category_identity_id' => 2,
                'type_file'            => 'png',
                'sequence'             => 5,
            ],
            [
                'id'                   => 7,
                'kode'                 => 'tentang',
                'name'                 => 'Tentang',
                'type'                 => 'Text',
                'value'                => '',
                'category_identity_id' => 3,
                'type_file'            => null,
                'sequence'             => 6,
            ],
            [
                'id'                   => 8,
                'kode'                 => 'copyright',
                'name'                 => 'Copyright',
                'type'                 => 'Text',
                'value'                => '© 2024 NEW CORE',
                'category_identity_id' => 3,
                'type_file'            => null,
                'sequence'             => 1,
            ],
        ];
        foreach ($listData as $data) {
            Identity::create($data);
        }
        $this->command->info('Identity table seeded!');
    }
}
