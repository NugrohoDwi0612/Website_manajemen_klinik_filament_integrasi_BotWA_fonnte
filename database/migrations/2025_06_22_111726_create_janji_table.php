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
        Schema::create('janji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pasien')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('id_jadwal_dokter')->constrained('jadwal_dokters')->onDelete('cascade'); // <-- UBAH KE id_jadwal
            $table->text('keluhan')->nullable();
            $table->enum('status', ['menunggu_konfirmasi', 'terjadwal', 'selesai', 'batal'])->default('menunggu_konfirmasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('janji');
    }
};
