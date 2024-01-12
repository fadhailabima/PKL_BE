<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function getAdmin()
    {
        // Mendapatkan user_id dari pengguna yang sedang login
        $user_id = Auth::id();

        // Mencari data admin berdasarkan user_id
        $admin = Admin::where('user_id', $user_id)->first();

        // Pastikan admin ditemukan
        if (!$admin) {
            return response()->json(['error' => 'Admin tidak ditemukan.'], 404);
        }

        // Memberikan respons JSON dengan data admin
        return response()->json(['admin' => $admin]);
    }
}
