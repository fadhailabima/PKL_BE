<?php

namespace App\Http\Controllers;

use App\Models\Rak;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\RakSlot;

class RakController extends Controller
{
    public function getAllRaks()
    {
        // Get all data from the "raks" table
        $raks = Rak::all();

        // Return a JSON response with the retrieved data
        return response()->json(['raks' => $raks]);
    }

    public function getByRakId($idrak)
    {
        try {
            // Gantilah 'id_rak' dengan nama kolom yang sesuai di tabel RakSlot
            $rakSlots = RakSlot::where('id_rak', $idrak)->get();

            return response()->json(['success' => true, 'data' => $rakSlots], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function tambahRakDanSlot(Request $request)
    {
        // Validasi request menggunakan Laravel Validator
        $validator = Validator::make($request->all(), [
            'kapasitas' => 'required|numeric',
            'status' => 'nullable|in:tersedia,tidak tersedia',
        ]);

        // Jika validasi gagal, kembalikan response error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Dapatkan ID rak terbaru
        $latestRak = Rak::orderBy('idrak', 'desc')->first();

        // Mendapatkan angka terakhir dari ID rak terbaru
        $lastNumber = $latestRak ? intval(substr($latestRak->idrak, 1)) : 0;

        // Increment angka dan format dengan nol di depan
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Membuat ID rak baru
        $idRak = 'R' . $newNumber;

        // Buat objek Rak baru
        $rak = new Rak();
        $rak->idrak = $idRak;
        $rak->kapasitas = $request->input('kapasitas');
        $rak->kapasitas_sisa = $request->input('kapasitas');
        $rak->status = $request->input('status') ?? 'tersedia'; // Menggunakan nilai default 'tersedia' jika status tidak diberikan
        // Simpan data ke database
        $rak->save();

        // Tambahkan rak slot sesuai kapasitas
        for ($i = 1; $i <= $rak->kapasitas; $i++) {
            $nextIdRakslot = $this->generateNextIdRakslot($rak->idrak);
            $rakslot = new RakSlot();
            $rakslot->id_rakslot = $nextIdRakslot;
            $rakslot->Xcoordinate = rand(1, 100); // Isi dengan nilai acak sesuai kebutuhan
            $rakslot->Ycoordinate = rand(1, 100); // Isi dengan nilai acak sesuai kebutuhan
            $rakslot->Zcoordinate = rand(1, 100); // Isi dengan nilai acak sesuai kebutuhan
            $rakslot->status = $request->input('status') ?? 'tersedia';
            // Simpan relasi dengan rak
            $rak->RakSlot()->save($rakslot);
        }

        // Berikan response JSON
        return response()->json(['message' => 'Rak dan slot berhasil ditambahkan', 'data' => $rak], 201);
    }

    private function generateNextIdRakslot($idRak)
    {
        // Temukan rakslot dengan id_rak yang sama
        $lastRakslot = Rakslot::where('id_rak', $idRak)->orderByDesc('id_rakslot')->first();

        // Variabel awal
        $newLetter = 'A';
        $newNumber = 1;

        if ($lastRakslot) {
            // Ambil huruf dari id_rakslot terakhir
            $lastLetter = substr($lastRakslot->id_rakslot, 0, 1);

            // Ambil angka dari id_rakslot terakhir
            $lastNumber = intval(substr($lastRakslot->id_rakslot, 1));

            // Jika id_rak berbeda, lanjutkan huruf ke alfabet selanjutnya dan reset angka ke 1
            if ($lastRakslot->id_rak !== $idRak) {
                $newLetter = chr(ord($lastLetter) + 1);
                $newNumber = 1;
            } else {
                // Jika id_rak sama, lanjutkan angka ke angka selanjutnya
                $newLetter = $lastLetter;
                $newNumber = $lastNumber + 1;

                // Jika huruf sudah mencapai 'Z', reset huruf ke 'A' dan reset angka ke 1
                if ($newLetter > 'Z') {
                    $newLetter = 'A';
                    $newNumber = 1;
                }
            }
        } else {
            // Jika belum ada rakslot untuk id_rak ini, auto-deteksi huruf pertama
            $previousRakslot = Rakslot::where('id_rak', '!=', $idRak)->orderByDesc('id_rakslot')->first();

            if ($previousRakslot) {
                $newLetter = chr(ord(substr($previousRakslot->id_rakslot, 0, 1)) + 1);
            }
        }

        // Format angka dengan nol di depan (3 digit)
        $formattedNumber = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Kombinasi id_rakslot baru
        $nextIdRakslot = $newLetter . $formattedNumber;

        return $nextIdRakslot;
    }
}
