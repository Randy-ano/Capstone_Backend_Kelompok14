<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Models\Kompetensi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Melengkapi talent menjadi 20 orang (termasuk akun demo talent@intellisys.test):
 * 13 profil lengkap + masuk talent pool, 6 belum lengkap.
 */
class TalentSeeder extends Seeder
{
    private const JUMLAH = 19;

    private const JUMLAH_LENGKAP = 13;

    public function run(): void
    {
        $kompetensi = Kompetensi::pluck('nama_kompetensi', 'id_kompetensi');
        $pendidikan = ['S1 Teknik Informatika', 'S1 Sistem Informasi', 'D3 Manajemen Informatika', 'S1 Ilmu Komputer', 'S2 Teknik Informatika'];

        for ($i = 1; $i <= self::JUMLAH; $i++) {
            $lengkap = $i <= self::JUMLAH_LENGKAP;

            $user = User::create([
                'email' => sprintf('talent%02d@intellisys.test', $i),
                'password' => 'password',
                'role' => Role::Talent,
            ]);

            // Profil belum lengkap: bergantian tanpa riwayat pendidikan atau tanpa kompetensi (R4).
            $tanpaPendidikan = ! $lengkap && $i % 2 === 0;
            $tanpaKompetensi = ! $lengkap && ! $tanpaPendidikan;
            $jenjang = fake()->randomElement($pendidikan);
            $tahunPengalaman = fake()->numberBetween(1, 8);

            $talent = $user->talent()->create([
                'nama_lengkap' => fake()->name(),
                'pendidikan' => $jenjang,
                'pengalaman_kerja' => $tahunPengalaman.' tahun pengalaman di bidang IT. '.fake()->sentence(),
                'status_kelengkapan' => $lengkap ? StatusKelengkapan::Lengkap : StatusKelengkapan::BelumLengkap,
            ]);

            if (! $tanpaPendidikan) {
                [$tingkat, $jurusan] = explode(' ', $jenjang, 2);
                $talent->riwayatPendidikan()->create([
                    'jenjang' => $tingkat,
                    'nama_institusi' => 'Universitas '.fake()->city(),
                    'jurusan' => $jurusan,
                    'tahun_mulai' => now()->year - $tahunPengalaman - 4,
                    'tahun_selesai' => now()->year - $tahunPengalaman,
                ]);
            }

            // Lama pengalaman (R9) = isi teks pengalaman_kerja: satu pekerjaan yang masih berjalan.
            $talent->pengalamanKerjaTalent()->create([
                'nama_perusahaan' => 'PT '.fake()->company(),
                'posisi' => fake()->randomElement(['Backend Developer', 'Frontend Developer', 'QA Engineer', 'Data Analyst', 'DevOps Engineer']),
                'jenis_pekerjaan' => 'Full-time',
                'lokasi' => fake()->city(),
                'tanggal_mulai' => now()->subYears($tahunPengalaman)->startOfMonth(),
                'masih_bekerja' => true,
            ]);

            if ($tanpaKompetensi) {
                continue;
            }

            $idDipilih = fake()->randomElements($kompetensi->keys()->all(), fake()->numberBetween(2, 6));
            $talent->kompetensi()->attach($idDipilih);

            if ($lengkap) {
                $talent->talentPool()->create([
                    'tanggal_masuk' => now()->subDays(fake()->numberBetween(1, 90)),
                    'status_ketersediaan' => StatusKetersediaan::Tersedia,
                    'tag_keahlian' => $kompetensi->only(array_slice($idDipilih, 0, 3))->implode(', '),
                ]);
            }
        }
    }
}
