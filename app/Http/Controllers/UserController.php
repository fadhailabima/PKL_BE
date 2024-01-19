<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'alamat' => 'nullable|string',
                'email' => 'nullable|max:255|email',
                'handphone' => 'nullable|string|regex:/^[0-9]+$/|between:10,12',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()], 422);
            }

            $user_id = Auth::id();

            // Mencari data admin berdasarkan user_id
            $admin = Admin::where('user_id', $user_id)->firstOrFail();

            // Log admin data before the update
            // \Log::info('Before Update: ' . json_encode($admin->toArray()));

            // Update profile fields
            $admin->fill([
                'alamat' => $request->input('alamat', $admin->alamat),
                'email' => $request->input('email', $admin->email),
                'handphone' => $request->input('handphone', $admin->handphone),
            ]);

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

            // Log admin data before saving
            // \Log::info('Before Save: ' . json_encode($admin->toArray()));

            $admin->save();

            // Log admin data after saving
            // \Log::info('After Save: ' . json_encode($admin->toArray()));

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            // \Log::error('Error updating admin profile: ' . $e->getMessage());

            // Log error message
            // \Log::error('Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}