<?php

namespace Database\Seeders;

use App\Models\MstKecamatan;
use Illuminate\Database\Seeder;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listData = [
            [
                'id'        => 1,
                'nama'      => 'Babakan Madang',
                'latitude'  => -6.571154174,
                'longitude' => 106.8695454,
            ],
            [
                'id'        => 2,
                'nama'      => 'Bojong Gede',
                'latitude'  => -6.477156841,
                'longitude' => 106.7991247,
            ],
            [
                'id'        => 3,
                'nama'      => 'Caringin',
                'latitude'  => -6.730039055,
                'longitude' => 106.8546,
            ],
            [
                'id'        => 4,
                'nama'      => 'Cariu',
                'latitude'  => -6.519772772,
                'longitude' => 107.135436,
            ],
            [
                'id'        => 5,
                'nama'      => 'Ciampea',
                'latitude'  => -6.578944896,
                'longitude' => 106.7019724,
            ],
            [
                'id'        => 6,
                'nama'      => 'Ciawi',
                'latitude'  => -6.701756095,
                'longitude' => 106.8819489,
            ],
            [
                'id'        => 7,
                'nama'      => 'Cibinong',
                'latitude'  => -6.482436464,
                'longitude' => 106.8383272,
            ],
            [
                'id'        => 8,
                'nama'      => 'Cibungbulang',
                'latitude'  => -6.57685851,
                'longitude' => 106.6566211,
            ],
            [
                'id'        => 9,
                'nama'      => 'Cigombong',
                'latitude'  => -6.747128239,
                'longitude' => 106.795828,
            ],
            [
                'id'        => 10,
                'nama'      => 'Cigudeg',
                'latitude'  => -6.509322743,
                'longitude' => 106.5564835,
            ],
            [
                'id'        => 11,
                'nama'      => 'Cijeruk',
                'latitude'  => -6.681489833,
                'longitude' => 106.7856695,
            ],
            [
                'id'        => 12,
                'nama'      => 'Cileungsi',
                'latitude'  => -6.398162032,
                'longitude' => 106.9818978,
            ],
            [
                'id'        => 13,
                'nama'      => 'Ciomas',
                'latitude'  => -6.608905964,
                'longitude' => 106.7597987,
            ],
            [
                'id'        => 14,
                'nama'      => 'Cisarua',
                'latitude'  => -6.70841256,
                'longitude' => 106.962275,
            ],
            [
                'id'        => 15,
                'nama'      => 'Ciseeng',
                'latitude'  => -6.45910014,
                'longitude' => 106.6793089,
            ],
            [
                'id'        => 16,
                'nama'      => 'Citeureup',
                'latitude'  => -6.520958169,
                'longitude' => 106.8872969,
            ],
            [
                'id'        => 17,
                'nama'      => 'Dramaga',
                'latitude'  => -6.586460232,
                'longitude' => 106.7348777,
            ],
            [
                'id'        => 18,
                'nama'      => 'Gunung Putri',
                'latitude'  => -6.388649099,
                'longitude' => 106.9377983,
            ],
            [
                'id'        => 19,
                'nama'      => 'Gunung Sindur',
                'latitude'  => -6.386377618,
                'longitude' => 106.6835726,
            ],
            [
                'id'        => 20,
                'nama'      => 'Jasinga',
                'latitude'  => -6.4695343,
                'longitude' => 106.4473287,
            ],
            [
                'id'        => 21,
                'nama'      => 'Jonggol',
                'latitude'  => -6.508173916,
                'longitude' => 107.0282682,
            ],
            [
                'id'        => 22,
                'nama'      => 'Kemang',
                'latitude'  => -6.507119018,
                'longitude' => 106.7372581,
            ],
            [
                'id'        => 23,
                'nama'      => 'Klapanunggal',
                'latitude'  => -6.483306278,
                'longitude' => 106.9525772,
            ],
            [
                'id'        => 24,
                'nama'      => 'Leuwiliang',
                'latitude'  => -6.645276474,
                'longitude' => 106.6077755,
            ],
            [
                'id'        => 25,
                'nama'      => 'Leuwisadeng',
                'latitude'  => -6.574267341,
                'longitude' => 106.589731,
            ],
            [
                'id'        => 26,
                'nama'      => 'Megamendung',
                'latitude'  => -6.675826922,
                'longitude' => 106.8947793,
            ],
            [
                'id'        => 27,
                'nama'      => 'Nanggung',
                'latitude'  => -6.661338533,
                'longitude' => 106.5440489,
            ],
            [
                'id'        => 28,
                'nama'      => 'Pamijahan',
                'latitude'  => -6.677206749,
                'longitude' => 106.656327,
            ],
            [
                'id'        => 29,
                'nama'      => 'Parung',
                'latitude'  => -6.440336688,
                'longitude' => 106.7171389,
            ],
            [
                'id'        => 30,
                'nama'      => 'Parung Panjang',
                'latitude'  => -6.373200099,
                'longitude' => 106.5539654,
            ],
            [
                'id'        => 31,
                'nama'      => 'Ranca Bungur',
                'latitude'  => -6.520005141,
                'longitude' => 106.7162116,
            ],
            [
                'id'        => 32,
                'nama'      => 'Rumpin',
                'latitude'  => -6.451117094,
                'longitude' => 106.6285044,
            ],
            [
                'id'        => 33,
                'nama'      => 'Sukajaya',
                'latitude'  => -6.647602381,
                'longitude' => 106.4662203,
            ],
            [
                'id'        => 34,
                'nama'      => 'Sukamakmur',
                'latitude'  => -6.586319372,
                'longitude' => 106.9904,
            ],
            [
                'id'        => 35,
                'nama'      => 'Sukaraja',
                'latitude'  => -6.576923212,
                'longitude' => 106.8411207,
            ],
            [
                'id'        => 36,
                'nama'      => 'Tajurhalang',
                'latitude'  => -6.472847319,
                'longitude' => 106.7590827,
            ],
            [
                'id'        => 37,
                'nama'      => 'Tamansari',
                'latitude'  => -6.667422416,
                'longitude' => 106.7429133,
            ],
            [
                'id'        => 38,
                'nama'      => 'Tanjungsari',
                'latitude'  => -6.615179024,
                'longitude' => 107.1373918,
            ],
            [
                'id'        => 39,
                'nama'      => 'Tenjo',
                'latitude'  => -6.372892209,
                'longitude' => 106.4716877,
            ],
            [
                'id'        => 40,
                'nama'      => 'Tenjolaya',
                'latitude'  => -6.65311745,
                'longitude' => 106.7063658,
            ],
        ];
        foreach ($listData as $data) {
            MstKecamatan::create($data);
        }
        $this->command->info('Kecamatan table seeded!');
    }
}
