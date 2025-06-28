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
            $table->unsignedBigInteger('id_janji')->nullable();
            $table->foreign('id_janji')
                ->references('id')->on('janji')
                ->onDelete('set null');
            $table->unsignedBigInteger('id_pasien')->nullable();
            $table->foreign('id_pasien')
                ->references('id')->on('pasiens')
                ->onDelete('set null');
            $table->unsignedBigInteger('id_jadwal_dokter')->nullable();
            $table->foreign('id_jadwal_dokter')
                ->references('id')->on('jadwal_dokters')
                ->onDelete('set null');

            $table->integer('nomor_antrian');
            $table->string('status')->default('menunggu');

            $table->dateTime('waktu_masuk')->useCurrent();
            $table->dateTime('waktu_dipanggil')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->timestamps();
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
