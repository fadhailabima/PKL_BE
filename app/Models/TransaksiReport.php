<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiReport extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $table = 'transaksi_reports';

    protected $fillable = [
        'receiptID',
        'id_rakslot',
        'id_rak',
        'jumlah',
        'nama_produk',
        'tanggal_transaksi',
        'jenis_transaksi',
        'expired_date',
        'kode_produksi',
    ];

    protected $guarded = [];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'receiptID', 'receiptID');
    }

    public function rakSlot()
    {
        return $this->belongsTo(RakSlot::class, 'id_rakslot', 'id_rakslot');
    }

    public function rak()
    {
        return $this->belongsTo(Rak::class, 'id_rak', 'idrak');
    }
}
