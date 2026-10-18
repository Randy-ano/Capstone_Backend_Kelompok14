<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_req_kompetensi', function (Blueprint $table) {
            $table->foreignId('id_job_req')->constrained('job_requirement', 'id_job_req')->cascadeOnDelete();
            $table->foreignId('id_kompetensi')->constrained('kompetensi', 'id_kompetensi')->cascadeOnDelete();
            $table->primary(['id_job_req', 'id_kompetensi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_req_kompetensi');
    }
};
