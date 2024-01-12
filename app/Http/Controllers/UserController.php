<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function signUp(Request $request)
    {
        // Validasi data yang diterima dari request untuk signup user
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
            'level' => 'required|in:admin,karyawan', // Validasi level
            'nama' => 'required|string', // Validasi nama
            'status' => 'required|in:Aktif,NON AKTIF', // Validasi status
            // Sesuaikan validasi sesuai kebutuhan Anda
        ]);

        // Jika validasi gagal, kirimkan respons JSON dengan pesan error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buat user baru
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'level' => $request->level, // Tambahkan level ke user
            // Tambahkan data lain sesuai kebutuhan
        ]);

        // Jika level adalah admin
        if ($request->level === 'admin') {
            $admin = new Admin();
            $admin->idadmin = $user->username; // Menggunakan username sebagai idadmin
            $admin->nama = $request->nama; // Tambahkan nama ke entitas Admin
            $admin->status = $request->status; // Tambahkan status ke entitas Admin
            $admin->user_id = $user->id; // Menghubungkan dengan user yang baru dibuat
            // Tambahkan data admin lainnya sesuai kebutuhan
            $admin->save();
        } else if ($request->level === 'karyawan') {
            $karyawan = new Karyawan();
            $karyawan->idkaryawan = $user->username; // Menggunakan username sebagai idkaryawan
            $karyawan->nama = $request->nama; // Tambahkan nama ke entitas Karyawan
            $karyawan->status = $request->status; // Tambahkan status ke entitas Karyawan
            $karyawan->user_id = $user->id; // Menghubungkan dengan user yang baru dibuat
            // Tambahkan data karyawan lainnya sesuai kebutuhan
            $karyawan->save();
        }

        // Kirim respons JSON untuk berhasil signup
        return response()->json(['message' => 'Signup successful', 'user' => $user], 201);
    }

    protected function okResponse($message, $data = [])
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    protected function unautheticatedResponse($message)
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $loginData['username'])->first();

        if (!$user || !Hash::check($loginData['password'], $user->password)) {
            return $this->unautheticatedResponse('Kombinasi username dan password tidak valid.');
        }

        $token = $user->createToken('authToken')->plainTextToken;
        $userData = array_merge($user->toArray(), ['token' => $token]);

        // Jika login gagal, kirimkan respons JSON dengan pesan error
        return $this->okResponse("Login Berhasil", ['user' => $userData]);
    }

    public function logout(Request $request)
    {
        // Lakukan proses logout untuk user yang sedang login
        $user = $request->user();

        if ($user) {

            $user->currentAccessToken()->delete(); // Menghapus token saat logout

            return response()->json(['message' => 'Logout berhasil']);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
