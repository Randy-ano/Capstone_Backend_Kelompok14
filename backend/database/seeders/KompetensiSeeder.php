<?php

namespace Database\Seeders;

use App\Models\Kompetensi;
use Illuminate\Database\Seeder;

class KompetensiSeeder extends Seeder
{
    public const DATA = [
        'Laravel' => 'Backend',
        'React' => 'Frontend',
        'Vue' => 'Frontend',
        'Node.js' => 'Backend',
        'Java' => 'Backend',
        'Spring' => 'Backend',
        'Python' => 'Backend',
        'Django' => 'Backend',
        'Flutter' => 'Mobile',
        'Kotlin' => 'Mobile',
        'SQL' => 'Database',
        'MySQL' => 'Database',
        'PostgreSQL' => 'Database',
        'MongoDB' => 'Database',
        'Docker' => 'DevOps',
        'Kubernetes' => 'DevOps',
        'AWS' => 'Cloud',
        'Git' => 'Tools',
        'REST API' => 'Backend',
        'UI/UX' => 'Design',
        'Figma' => 'Design',
        'QA Manual' => 'Quality Assurance',
        'Selenium' => 'Quality Assurance',
        'Scrum' => 'Project Management',
        'Linux' => 'Infrastruktur',
        'Network' => 'Infrastruktur',
        'Cyber Security' => 'Security',
        'Data Analysis' => 'Data',
        'Power BI' => 'Data',
        'Excel' => 'Data',
    ];

    public function run(): void
    {
        foreach (self::DATA as $nama => $kategori) {
            Kompetensi::firstOrCreate(['nama_kompetensi' => $nama], ['kategori' => $kategori]);
        }
    }
}
