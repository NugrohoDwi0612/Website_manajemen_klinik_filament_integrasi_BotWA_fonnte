<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePolikliniksTable extends Migration
{
    public function up()
    {
        Schema::create('polikliniks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_poliklinik');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('polikliniks');
    }
}
