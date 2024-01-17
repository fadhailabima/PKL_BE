<?php

namespace App\Http\Controllers;

use App\Models\Rak;
use App\Models\RakSlot;
use Illuminate\Http\Request;

class RakController extends Controller
{
    public function getAllRaks()
    {
        // Get all data from the "raks" table
        $raks = Rak::all();

        // Return a JSON response with the retrieved data
        return response()->json(['raks' => $raks]);
    }

    public function getRakbyID($idrak)
    {
        try {
            // Cari data rak berdasarkan ID
            $rak = Rak::findOrFail($idrak);

            // Anda dapat menyesuaikan logika ini sesuai kebutuhan
            return response()->json(['success' => true, 'data' => $rak], 200);
        } catch (\Exception $e) {
            // Tangani kesalahan jika rak tidak ditemukan
            return response()->json(['success' => false, 'message' => 'Rak not found'], 404);
        }
    }

    public function getRakSlot()
    {
        try {
            $rakSlots = RakSlot::all();

            return response()->json(['success' => true, 'data' => $rakSlots], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
}
