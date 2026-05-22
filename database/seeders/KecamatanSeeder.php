<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Pekon;
use Illuminate\Database\Seeder;

class KecamatanSeeder extends Seeder
{
    public function run(): void
    {
        // 9 Kecamatan di Kabupaten Pringsewu beserta beberapa pekon contoh.
        $data = [
            'Pringsewu' => ['Pringsewu Utara', 'Pringsewu Selatan', 'Pringsewu Barat', 'Pringsewu Timur', 'Margakaya', 'Sidoharjo', 'Podomoro', 'Bumi Arum'],
            'Gadingrejo' => ['Gadingrejo', 'Wonodadi', 'Wates', 'Tegalsari', 'Tambahrejo'],
            'Sukoharjo' => ['Sukoharjo I', 'Sukoharjo II', 'Sukoharjo III', 'Pandansari', 'Pandansurat'],
            'Pardasuka' => ['Pardasuka', 'Pardasuka Timur', 'Sukanegara', 'Tanjung Rusia'],
            'Ambarawa' => ['Ambarawa', 'Ambarawa Barat', 'Ambarawa Timur', 'Sumber Agung'],
            'Pagelaran' => ['Pagelaran', 'Patoman', 'Pasir Ukir', 'Sukaratu'],
            'Pagelaran Utara' => ['Fajar Mulya', 'Margosari', 'Lugu Sari'],
            'Banyumas' => ['Banyumas', 'Banyu Wangi', 'Sri Rahayu', 'Mulyorejo'],
            'Adiluwih' => ['Adiluwih', 'Bandung Baru', 'Srikaton', 'Tunggul Pawenang'],
        ];

        $i = 1;
        foreach ($data as $kecName => $pekons) {
            $kec = Kecamatan::create([
                'code' => sprintf('KEC%02d', $i++),
                'name' => $kecName,
            ]);

            foreach ($pekons as $pekonName) {
                Pekon::create([
                    'kecamatan_id' => $kec->id,
                    'name' => $pekonName,
                    'type' => 'pekon',
                ]);
            }
        }
    }
}
