<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarisTable extends Migration
{
    public function up()
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->integer('jumlah');
            $table->string('kondisi');
            $table->date('tanggal_pembelian');
            $table->text('keterangan')->nullable();
            $table->foreignId('id_poliklinik')->constrained('polikliniks')->onDelete('cascade'); // Tambahkan ini
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventaris');
    }
}
