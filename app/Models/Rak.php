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
        'status'
    ];

    protected $guarded = [];


    public function RakSlot()
    {
        return $this->hasMany(RakSlot::class, 'id_rak', 'idrak');
    }

    public function transaksiReport()
    {
        return $this->hasMany(TransaksiReport::class, 'id_rak', 'idrak');
    }
}
