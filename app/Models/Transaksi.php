<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksis';
    protected $primaryKey = 'receiptID';
    protected $keyType = 'string';

    protected $fillable = [
        'id_rak',
        'id_produk',
        'id_user',
        'id_slotrak',
        'jumlah',
        'tanggal_transaksi',
        'jenis_transaksi',
    ];

    protected $guarded = [];

    public function rak()
    {
        return $this->belongsTo(Rak::class, 'id_rak', 'idrak');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'idproduk');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'idkaryawan');
    }

    public function slotrak()
    {
        return $this->belongsTo(RakSlot::class, 'id_slotrak', 'id_rakslot');
    }
}
