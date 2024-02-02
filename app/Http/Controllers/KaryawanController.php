<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

    public function updateProfileKaryawan(Request $request)
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
            $karyawan = Karyawan::where('user_id', $user_id)->firstOrFail();

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

            $karyawan->update($dataToUpdate);

            // Handle photo update
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                // Delete old photo if exists
                if ($karyawan->foto) {
                    Storage::delete('public/photo/' . $karyawan->foto);
                }

                // Save new photo
                $file = $request->file('foto');
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/photo/', $fileName);
                $karyawan->foto = $fileName;
            }

            $karyawan->save();

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui', 'karyawan' => $karyawan, 'inputs' => $request->all()]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
