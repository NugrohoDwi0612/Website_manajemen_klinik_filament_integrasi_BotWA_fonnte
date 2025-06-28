<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekam_medis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pasien')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('id_dokter')->constrained('dokters')->onDelete('cascade');
            $table->foreignId('id_antrian')->constrained('antrians')->onDelete('cascade');
            $table->text('diagnosa');
            $table->text('catatan')->nullable();
            $table->date('tanggal_periksa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekam_medis');
    }
};
