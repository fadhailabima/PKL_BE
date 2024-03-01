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
        $rak->status = $request->input('status') ?? 'Tersedia'; // Menggunakan nilai default 'tersedia' jika status tidak diberikan
        // Simpan data ke database
        $rak->save();

        // Tambahkan rak slot sesuai kapasitas
        $lantai = 0; // Inisialisasi variabel lantai
        for ($i = 1; $i <= 40; $i++) {
            // Perhitungan untuk posisi kanan atau kiri
            $posisi = ($i <= 20) ? 'Kanan' : 'Kiri';

            // Perhitungan untuk lantai
            if ($i > 20 && $i % 20 === 1) {
                $lantai = 0; // Reset lantai ke 1 setelah selesai iterasi rakslot kanan
            }

            $nextIdRakslot = $this->generateNextIdRakslot($rak->idrak, $lantai);

            $rakslot = new RakSlot();
            $rakslot->id_rakslot = $nextIdRakslot;
            $rakslot->id_rak = $rak->idrak;

            // Ambil nilai kapasitas_maksimal dari request atau default ke 300 jika tidak ada
            $rakslot->kapasitas_maksimal = $request->input('kapasitas_maksimal');

            $rakslot->kapasitas_terpakai = '0';
            $rakslot->posisi = $posisi;
            $rakslot->lantai = (string) $lantai;
            $rakslot->status = $request->input('status') ?? 'tersedia';

            // Simpan relasi dengan rak
            $rak->RakSlot()->save($rakslot);

            // Increment lantai setiap 4 iterasi
            if ($i % 4 === 0) {
                $lantai++;
            }
        }
        // Berikan response JSON
        return response()->json(['message' => 'Rak dan slot berhasil ditambahkan', 'data' => $rak], 201);
    }

    private function generateNextIdRakslot($idRak, $lantai)
    {
        // Temukan rakslot terakhir secara keseluruhan
        $lastRakslot = Rakslot::orderByDesc('id_rakslot')->first();

        // Variabel awal
        $newLetter = 'A';
        $newNumber = 1;

        if ($lastRakslot) {
            // Ambil huruf dari id_rakslot terakhir
            $lastLetter = substr($lastRakslot->id_rakslot, -4, 1);

            // Jika id_rak berbeda dari id_rak rakslot terakhir, lanjutkan huruf ke huruf selanjutnya
            if ($idRak != $lastRakslot->id_rak) {
                $newLetter = chr(ord($lastLetter) + 1);
            } else {
                $newLetter = $lastLetter;
            }

            // Jika lantai sama dengan lantai rakslot terakhir dan id_rak sama, lanjutkan angka ke angka selanjutnya
            if ($lantai == $lastRakslot->lantai && $idRak == $lastRakslot->id_rak) {
                // Ambil angka dari id_rakslot terakhir
                $lastNumber = intval(substr($lastRakslot->id_rakslot, -3));
                $newNumber = $lastNumber + 1;
            }

            // Jika huruf sudah mencapai 'Z', reset huruf ke 'A' dan reset angka ke 1
            if ($newLetter > 'Z') {
                $newLetter = 'A';
                $newNumber = 1;
            }
        }

        // Jika lantai adalah '0', tambahkan 'XX' ke newLetter dan reset angka ke 1
        if ($lantai == '0') {
            $newLetter = 'XX' . $newLetter;
            $newNumber = 1;
        }

        // Format angka dengan nol di depan (3 digit)
        $formattedNumber = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Kombinasi id_rakslot baru
        $nextIdRakslot = $newLetter . $formattedNumber;

        while (Rakslot::where('id_rakslot', $nextIdRakslot)->exists()) {
            // If it does, increment the number and generate a new id_rakslot
            $newNumber++;
            $formattedNumber = str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            $nextIdRakslot = $newLetter . $formattedNumber;
        }

        return $nextIdRakslot;
    }
    public function changeStatusRak(Request $request, $idrak)
    {

        $rak = Rak::find($idrak);

        if (!$rak) {
            return response()->json(['error' => 'Rak not found.'], 404);
        }

        $rak->status = $request->status;
        $rak->save();

        return response()->json(['success' => 'Rak status updated successfully.']);
    }
}
