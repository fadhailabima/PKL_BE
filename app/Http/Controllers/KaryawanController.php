<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KaryawanController extends Controller
{
    public function getKaryawan()
    {
        // Mendapatkan user_id dari pengguna yang sedang login
        $user_id = Auth::id();

        // Mencari data admin berdasarkan user_id
        $karyawan = Karyawan::where('user_id', $user_id)->first();

        // get image_url
        $karyawan->image_url = $karyawan->foto ? url('api/public/storage/photo/' . $karyawan->foto) : null;

        // Pastikan admin ditemukan
        if (!$karyawan) {
            return response()->json(['error' => 'Admin tidak ditemukan.'], 404);
        }

        // Memberikan respons JSON dengan data karyawan
        return response()->json(['karyawan' => $karyawan], 200);
    }
}
