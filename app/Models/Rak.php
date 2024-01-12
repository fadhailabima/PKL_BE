<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rak extends Model
{
    use HasFactory;

    
    protected $table = 'raks';
    protected $primaryKey = 'idrak';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kapasitas',
        'kapasitas_sisa',
        'status'
    ];

    protected $guarded = [];


    public function RakSlot()
    {
        return $this->hasMany(RakSlot::class, 'id_rak', 'idrak');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_rak', 'idrak');
    }
}
