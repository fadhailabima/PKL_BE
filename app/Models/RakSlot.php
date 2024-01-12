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
        'Xcoordinate',
        'Ycoordinate',
        'Zcoordinate',
        'status'
    ];

    protected $guarded = [];

    public function rak()
    {
        return $this->belongsTo(Rak::class, 'id_rak', 'idrak');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_slotrak', 'id_rakslot');
    }
}
