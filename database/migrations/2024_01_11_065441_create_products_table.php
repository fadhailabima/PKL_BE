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
        Schema::create('products', function (Blueprint $table) {
            $table->string('idproduk')->primary();
            $table->string('namaproduk');
            $table->string('value');
            $table->enum('jenisproduk', ['Pupuk Tunggal', 'Pupuk Majemuk', 'Pupuk Soluble', 'Pupuk Organik', 'Pestisida'])->nullable();
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
        Schema::dropIfExists('products');
    }
};
