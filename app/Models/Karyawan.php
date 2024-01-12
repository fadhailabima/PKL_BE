<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawans';
    protected $primaryKey = 'idkaryawan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nama',
        'alamat',
        'email',
        'handphone',
        'status',
        'foto',
        'user_id',
    ];

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_karyawan', 'idkaryawan');
    }
}
