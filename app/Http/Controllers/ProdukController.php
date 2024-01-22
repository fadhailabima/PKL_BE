<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function getProduk()
    {
        // Get all data from the "raks" table
        $produks = Produk::all();

        // Return a JSON response with the retrieved data
        return response()->json(['produk' => $produks]);
    }

    public function deleteProduk($idproduk)
    {
        // Cari produk berdasarkan ID
        $produk = Produk::find($idproduk);

        if (!$produk) {
            return response()->json(['message' => 'Produk not found'], 404);
        }

        // Hapus produk
        $produk->delete();

        return response()->json(['message' => 'Produk deleted successfully']);
    }
}
