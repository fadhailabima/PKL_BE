<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisProduk extends Model
{
    use HasFactory;

    protected $table = 'jenis_produks';

    protected $fillable = [
        'jenisproduk',
    ];

    protected $guarded = [];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'idjenisproduk', 'id');
    }
}
