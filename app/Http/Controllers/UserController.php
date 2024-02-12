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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'level' => $request->level,
        ]);

        // Jika level adalah admin
        if ($request->level === 'admin') {
            $admin = new Admin();
            $admin->idadmin = $user->username;
            $admin->nama = $request->nama;
            $admin->status = 'Aktif';
            $admin->user_id = $user->id;
            $admin->save();
        } else if ($request->level === 'karyawan') {
            $karyawan = new Karyawan();
            $karyawan->idkaryawan = $user->username;
            $karyawan->nama = $request->nama;
            $karyawan->status = 'Aktif';
            $karyawan->user_id = $user->id;
            $karyawan->save();
        }

        // dd($request->all());

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

        $user = User::where('username', $loginData['username'])
                     ->with(['admin' => function ($query) {
                         $query->where('status', 'Aktif');
                     }, 'karyawan' => function ($query) {
                         $query->where('status', 'Aktif');
                     }])
                     ->first();

        if (!$user || !Hash::check($loginData['password'], $user->password)) {
            return $this->unautheticatedResponse('Kombinasi username dan password tidak valid.');
        }

        if (!$user->admin && !$user->karyawan) {
            return $this->unautheticatedResponse('Akun tidak aktif.');
        }

        $token = $user->createToken('authToken')->plainTextToken;
        $userData = array_merge($user->toArray(), ['token' => $token]);

        // encrypt the level to be used in the frontend with hash key
        // setup the hash key        

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
