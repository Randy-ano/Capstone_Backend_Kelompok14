<?php

namespace App\Services;

use App\Models\Talent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TalentCvService
{
public function generate(Talent $talent): Talent
{
    $talent->load([
        'kompetensi',
        'riwayatPendidikan',
        'pengalamanKerjaTalent',
        'sertifikat',
        'bahasa',
    ]);

    $pdf = Pdf::loadView('pdf.talent-cv', [
        'talent' => $talent,
    ]);

    $path = "cv/talent-{$talent->id_talent}.pdf";

    Storage::disk('public')->put($path, $pdf->output());

    $talent->update([
        'file_cv' => $path,
    ]);

    return $talent->fresh()->load([
        'kompetensi',
        'riwayatPendidikan',
        'pengalamanKerjaTalent',
        'sertifikat',
        'bahasa',
    ]);
}
}