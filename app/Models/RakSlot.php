<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RakSlot extends Model
{
    use HasFactory;

    protected $table = 'rakslots';
    protected $primaryKey = 'id_rakslot';
    protected $keyType = 'string';

    protected $fillable = [
        'id_rak',
        'posisi',
        'lantai',
        'kapasitas',
        'status'
    ];

    protected $guarded = [];

    public function rak()
    {
        return $this->belongsTo(Rak::class, 'id_rak', 'idrak');
    }

    public function transaksiReport()
    {
        return $this->hasMany(TransaksiReport::class, 'id_rakslot', 'id_rakslot');
    }
}
