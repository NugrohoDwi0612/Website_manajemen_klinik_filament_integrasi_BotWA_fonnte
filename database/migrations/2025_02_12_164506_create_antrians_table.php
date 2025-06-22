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
        Schema::create('antrians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pasien')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('id_jadwal_dokter')->constrained('jadwal_dokters')->onDelete('cascade');
            $table->integer('nomor_antrian');
            $table->enum('status', ['menunggu', 'diperiksa', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->timestamps();
            $table->index('nomor_antrian');
            $table->index('status');
            $table->index(['id_jadwal_dokter', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrians');
    }
};
