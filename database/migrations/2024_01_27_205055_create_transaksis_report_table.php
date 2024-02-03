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
        Schema::create('transaksi_reports', function (Blueprint $table) {
            $table->id();
            $table->string('receiptID');
            $table->string('id_rak');
            $table->string('id_rakslot');
            $table->string('jumlah');
            $table->string('nama_produk');
            $table->date('expired_date')->nullable();
            $table->enum('jenis_transaksi', ['masuk', 'keluar']);
            $table->foreign('receiptID')->references('receiptID')->on('transaksis')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_rak')->references('idrak')->on('raks')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_rakslot')->references('id_rakslot')->on('rakslots')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::table('transaksi_reports', function (Blueprint $table) {
            $table->dropForeign(['receiptID']);
            $table->dropForeign(['id_rak']);
            $table->dropForeign(['id_rakslot']);
        });
        Schema::dropIfExists('transaksi_reports');
    }
};
