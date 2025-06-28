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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pasien')->constrained('pasiens')->onDelete('cascade');
            $table->foreignId('id_rekam_medis')->nullable()->constrained('rekam_medis')->onDelete('set null');
            $table->decimal('total_biaya', 10, 2);
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['pending', 'lunas', 'batal'])->default('pending');
            $table->date('tanggal_pembayaran');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
