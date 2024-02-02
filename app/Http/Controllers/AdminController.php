<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\Produk;
use App\Models\Rak;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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

    public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'alamat' => 'nullable|string',
                'email' => 'nullable|max:255|email',
                'handphone' => 'nullable|string|regex:/^[0-9]+$/|between:10,12',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
            ], [
                // Pesan error kustom
                'handphone.regex' => 'Nomor handphone hanya boleh berisi angka.',
                'handphone.between' => 'Nomor handphone harus antara 10 hingga 12 karakter.',
                'foto.image' => 'File harus berupa gambar.',
                'foto.mimes' => 'Gambar harus berformat: jpeg, png, jpg, gif.',
                'foto.max' => 'Ukuran gambar tidak boleh lebih dari 3MB.',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()], 422);
            }

            $user_id = Auth::id();

            // Mencari data admin berdasarkan user_id
            $admin = Admin::where('user_id', $user_id)->firstOrFail();

            // Update profile fields
            $dataToUpdate = [];

            if ($request->input('alamat')) {
                $dataToUpdate['alamat'] = $request->input('alamat');
            }

            if ($request->input('email')) {
                $dataToUpdate['email'] = $request->input('email');
            }

            if ($request->input('handphone')) {
                $dataToUpdate['handphone'] = $request->input('handphone');
            }

            $admin->update($dataToUpdate);

            // Handle photo update
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Delete old photo if exists
                if ($admin->foto) {
                    Storage::delete('public/photo/' . $admin->foto);
                }

                // Save new photo
                $file = $request->file('foto');
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/photo/', $fileName);
                $admin->foto = $fileName;
            }

            $admin->save();

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui', 'admin' => $admin, 'inputs' => $request->all()]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
