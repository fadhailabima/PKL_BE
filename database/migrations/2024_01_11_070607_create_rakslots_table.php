<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rakslots', function (Blueprint $table) {
            $table->string('id_rakslot')->primary();
            $table->string('id_rak');
            $table->string('kapasitas');
            $table->enum('posisi', ['kanan', 'kiri'])->nullable();
            $table->enum('lantai', ['1', '2', '3', '4'])->nullable();
            $table->enum('status', ['tersedia', 'tidak tersedia'])->nullable();
            $table->foreign('id_rak')->references('idrak')->on('raks')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rakslots', function (Blueprint $table) {
            $table->dropForeign(['id_rak']);
        });
        Schema::dropIfExists('rakslots');
    }
};
