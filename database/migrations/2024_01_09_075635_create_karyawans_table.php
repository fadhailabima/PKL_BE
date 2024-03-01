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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->string('idkaryawan')->primary();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->string('email')->nullable();
            $table->string('handphone')->nullable();
            $table->enum('status', ['Aktif', 'Non Aktif'])->default('Aktif');
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('karyawans');
    }
};
