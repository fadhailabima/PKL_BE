<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'idproduk';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'namaproduk',
        'jenisproduk',
        'value',
        'idjenisproduk'
    ];

    protected $guarded = [];

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_produk', 'idproduk');
    }

    public function jenisProduk()
    {
        return $this->belongsTo(JenisProduk::class, 'idjenisproduk', 'id');
    }
}
