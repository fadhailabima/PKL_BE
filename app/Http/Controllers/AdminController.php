<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\Produk;
use App\Models\Rak;
use App\Models\Transaksi;
use App\Models\User;
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

        // get image_url
        $admin->image_url = $admin->foto ? url('api/public/storage/photo/' . $admin->foto) : null;

        // Pastikan admin ditemukan
        if (!$admin) {
            return response()->json(['error' => 'Admin tidak ditemukan.'], 404);
        }

        // Memberikan respons JSON dengan data admin
        return response()->json(['admin' => $admin]);
    }

    public function getStatistik()
    {
        $jumlahRak = Rak::count();
        $jumlahProduk = Produk::count();
        $jumlahUser = User::count();
        $jumlahTransaksi = Transaksi::count();

        return response()->json([
            'jumlah_rak' => $jumlahRak,
            'jumlah_produk' => $jumlahProduk,
            'jumlah_user' => $jumlahUser,
            'jumlah_transaksi' => $jumlahTransaksi,
        ]);
    }

    public function manageUser()
    {
        // Mengambil data user beserta informasi terkait dari tabel Admin dan Karyawan
        $users = User::with('admin', 'karyawan')
            ->orderBy('level', 'asc') // Sesuaikan dengan nama kolom yang menunjukkan level (admin/karyawan)
            ->get();

        return response()->json(['users' => $users]);
    }

    public function deleteUser($id)
    {
        // Find user by ID
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete associated records in the admins table
        $user->admin()->delete();

        // Delete associated records in the karyawan table
        $user->karyawan()->delete();

        // Delete the user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function changeStatus($id, Request $request)
    {

        // Find the user
        $user = User::find($id);

        // If user not found, return an error
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }


        // Check the user role and update the status
        if ($user->level == 'admin') {
            $admin = Admin::where('user_id', $user->id)->first();
            if ($admin) {
                $admin->status = $request->input('status');
                $admin->save();
            }
        } elseif ($user->level == 'karyawan') {
            $karyawan = Karyawan::where('user_id', $user->id)->first();
            if ($karyawan) {
                $karyawan->status = $request->input('status');
                $karyawan->save();
            }
        }

        return response()->json(['message' => 'Status user berhasil diubah'], 200);
    }
}
