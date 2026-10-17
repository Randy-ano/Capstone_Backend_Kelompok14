<?php

namespace Database\Seeders;

use App\Models\Klien;
use Illuminate\Database\Seeder;

class KlienSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_perusahaan' => 'PT Maju Jaya', 'alamat' => 'Jl. Jend. Sudirman No. 21, Jakarta Selatan', 'no_telepon' => '021-5550101', 'penanggung_jawab' => 'Rudi Hartanto'],
            ['nama_perusahaan' => 'PT Sinar Digital Nusantara', 'alamat' => 'Jl. Asia Afrika No. 88, Bandung', 'no_telepon' => '022-4230202', 'penanggung_jawab' => 'Dewi Lestari'],
            ['nama_perusahaan' => 'CV Teknologi Andalan', 'alamat' => 'Jl. Raya Darmo No. 45, Surabaya', 'no_telepon' => '031-5670303', 'penanggung_jawab' => 'Agus Salim'],
        ];

        foreach ($data as $klien) {
            Klien::firstOrCreate(['nama_perusahaan' => $klien['nama_perusahaan']], $klien);
        }
    }
}
