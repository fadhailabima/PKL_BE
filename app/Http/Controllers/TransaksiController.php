<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    public function getAllTransaksi()
    {
        $transaksis = Transaksi::with('karyawan')->get();

        return response()->json(['transaksis' => $transaksis]);
    }
}
