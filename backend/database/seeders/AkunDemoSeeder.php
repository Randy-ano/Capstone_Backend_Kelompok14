<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 1 akun demo per role, semua berpassword "password".
 */
class AkunDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pegawai = [
            ['am@intellisys.test', Role::AccountManager, 'Andi Pratama', 'Senior Account Manager', '0812-1111-0001'],
            ['ts@intellisys.test', Role::TalentSupport, 'Rina Kartika', 'Talent Support', '0812-1111-0002'],
            ['manager@intellisys.test', Role::Manager, 'Hendra Gunawan', 'Manager', '0812-1111-0003'],
        ];

        foreach ($pegawai as [$email, $role, $nama, $jabatan, $telepon]) {
            $this->akun($email, $role)->pegawai()->create([
                'nama_pegawai' => $nama,
                'jabatan' => $jabatan,
                'no_telepon' => $telepon,
            ]);
        }

        $this->akun('hr@intellisys.test', Role::HrClient)->hrClient()->create([
            'id_klien' => Klien::where('nama_perusahaan', 'PT Maju Jaya')->value('id_klien'),
            'nama_hr' => 'Sari Wulandari',
            'jabatan' => 'HR Manager',
        ]);

        $talent = $this->akun('talent@intellisys.test', Role::Talent)->talent()->create([
            'nama_lengkap' => 'Budi Santoso',
            'pendidikan' => 'S1 Teknik Informatika',
            'pengalaman_kerja' => '3 tahun sebagai Backend Developer (Laravel) di perusahaan e-commerce.',
            'status_kelengkapan' => StatusKelengkapan::Lengkap,
        ]);
        $kompetensi = ['Laravel', 'MySQL', 'REST API', 'Git'];
        $talent->kompetensi()->attach(Kompetensi::whereIn('nama_kompetensi', $kompetensi)->pluck('id_kompetensi'));
        $talent->talentPool()->create([
            'tanggal_masuk' => now(),
            'status_ketersediaan' => StatusKetersediaan::Tersedia,
            'tag_keahlian' => implode(', ', $kompetensi),
        ]);
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
