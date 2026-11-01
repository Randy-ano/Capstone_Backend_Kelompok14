<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Klien;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo per role (password "password") ditambah pegawai dan HR Client lain agar daftar tidak sepi.
 * Akun talent ada di TalentSeeder.
 */
class AkunDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pegawai = [
            ['am@intellisys.test', Role::AccountManager, 'Andi Pratama', 'Senior Account Manager', '0812-1111-0001'],
            ['ts@intellisys.test', Role::TalentSupport, 'Rina Kartika', 'Talent Support', '0812-1111-0002'],
            ['manager@intellisys.test', Role::Manager, 'Hendra Gunawan', 'Manager', '0812-1111-0003'],
            ['am02@intellisys.test', Role::AccountManager, 'Yusuf Ramadhan', 'Account Manager', '0812-1111-0004'],
            ['ts02@intellisys.test', Role::TalentSupport, 'Fitri Handayani', 'Talent Support', '0812-1111-0005'],
        ];

        foreach ($pegawai as [$email, $role, $nama, $jabatan, $telepon]) {
            $this->akun($email, $role)->pegawai()->create([
                'nama_pegawai' => $nama,
                'jabatan' => $jabatan,
                'no_telepon' => $telepon,
            ]);
        }

        $hr = [
            ['hr@intellisys.test', 'PT Maju Jaya', 'Sari Wulandari', 'HR Manager'],
            ['hr02@intellisys.test', 'PT Maju Jaya', 'Bambang Sutrisno', 'Talent Acquisition Lead'],
            ['hr03@intellisys.test', 'PT Sinar Digital Nusantara', 'Dewi Anggraini', 'HR Manager'],
            ['hr04@intellisys.test', 'PT Sinar Digital Nusantara', 'Lukman Hakim', 'HR Business Partner'],
            ['hr05@intellisys.test', 'CV Teknologi Andalan', 'Retno Palupi', 'HR & General Affairs'],
            ['hr06@intellisys.test', 'PT Nusantara Retail Indonesia', 'Agnes Tanjung', 'HR Manager'],
        ];

        foreach ($hr as [$email, $klien, $nama, $jabatan]) {
            $this->akun($email, Role::HrClient)->hrClient()->create([
                'id_klien' => Klien::where('nama_perusahaan', $klien)->value('id_klien'),
                'nama_hr' => $nama,
                'jabatan' => $jabatan,
            ]);
        }
    }

    private function akun(string $email, Role $role): User
    {
        return User::create([
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
