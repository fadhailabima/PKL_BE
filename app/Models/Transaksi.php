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
        'id_produk',
        'id_karyawan',
        'jumlah',
        'tanggal_transaksi',
        'jenis_transaksi',
        'tanggal_expired',
        'kode_produksi',
    ];

    protected $guarded = [];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'idproduk');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'idkaryawan');
    }

    public function transaksiReport()
    {
        return $this->hasMany(TransaksiReport::class, 'receiptID', 'receiptID');
    }
}
