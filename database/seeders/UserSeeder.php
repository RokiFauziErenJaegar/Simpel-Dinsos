<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Kecamatan;
use App\Models\Pekon;
use App\Models\PpksProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $kec = Kecamatan::where('name', 'Pringsewu')->first();
        $pekon = Pekon::where('name', 'Pringsewu Utara')->first();

        $accounts = [
            [
                'name' => 'Administrator Sistem',
                'email' => 'admin@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::Admin->value,
                'phone' => '08220000001',
            ],
            [
                'name' => 'Debi Hardian, S.Pi., M.Si.',
                'email' => 'kadis@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::Kadis->value,
                'phone' => '08220000002',
            ],
            [
                'name' => 'Sekretaris Dinas',
                'email' => 'sekretaris@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::Sekretaris->value,
                'phone' => '08220000003',
            ],
            [
                'name' => 'Kabid Rehabilitasi Sosial',
                'email' => 'kabid.rehsos@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::Kabid->value,
                'phone' => '08220000004',
            ],
            [
                'name' => 'Rina Verifikator',
                'email' => 'petugas@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::Petugas->value,
                'phone' => '08220000005',
            ],
            [
                'name' => 'Operator Pekon Pringsewu Utara',
                'email' => 'operator.pekon@dinsospringsewu.test',
                'password' => 'password',
                'role' => UserRole::OperatorPekon->value,
                'phone' => '08220000006',
                'kecamatan_id' => $kec?->id,
                'pekon_id' => $pekon?->id,
            ],
        ];

        foreach ($accounts as $data) {
            $data['password'] = Hash::make($data['password']);
            $data['is_active'] = true;
            $data['email_verified_at'] = now();
            User::create($data);
        }

        // Akun warga demo
        $warga = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@warga.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Warga->value,
            'nik' => '1871030000000001',
            'phone' => '081200000001',
            'address' => 'Pekon Pringsewu Utara, RT 02 RW 01',
            'kecamatan_id' => $kec?->id,
            'pekon_id' => $pekon?->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        PpksProfile::create([
            'user_id' => $warga->id,
            'birth_date' => '1985-04-12',
            'birth_place' => 'Pringsewu',
            'gender' => 'Laki-laki',
            'occupation' => 'Buruh tani',
            'family_card_no' => '1871030000000001',
            'dtsen_desil' => 3,
            'dtsen_verified_at' => now()->subMonths(2),
        ]);

        User::create([
            'name' => 'Siti Maesaroh',
            'email' => 'siti@warga.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Warga->value,
            'nik' => '1871030000000002',
            'phone' => '081200000002',
            'address' => 'Pekon Gadingrejo, RT 04 RW 02',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
