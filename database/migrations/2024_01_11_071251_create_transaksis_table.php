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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->string('receiptID')->primary();
            $table->string('id_produk');
            $table->string('id_karyawan');
            $table->string('jumlah');
            $table->date('tanggal_transaksi');
            $table->date('tanggal_expired')->nullable();
            $table->string('kode_produksi')->nullable();
            $table->enum('jenis_transaksi', ['Masuk', 'Keluar'])->nullable();
            $table->foreign('id_produk')->references('idproduk')->on('products')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_karyawan')->references('idkaryawan')->on('karyawans')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['id_produk']);
            $table->dropForeign(['id_karyawan']);
        });
        Schema::dropIfExists('transaksis');
    }
};
