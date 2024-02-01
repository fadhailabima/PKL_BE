<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function tambahProduk(Request $request)
    {
        // Validasi request menggunakan Laravel Validator
        $validator = Validator::make($request->all(), [
            'namaproduk' => 'required|string',
            'jenisproduk' => 'required|in:Pupuk Tunggal,Pupuk Majemuk,Pupuk Soluble,Pupuk Organik,Pestisida',
            'value' => 'required|string',
        ]);

        // Jika validasi gagal, kembalikan response error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Dapatkan ID produk terbaru
        $latestProduk = Produk::orderBy('idproduk', 'desc')->first();

        // Mendapatkan angka terakhir dari ID produk terbaru
        $lastNumber = $latestProduk ? intval(substr($latestProduk->idproduk, 1)) : 0;

        // Increment angka dan format dengan nol di depan
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Membuat ID produk baru
        $idProduk = 'P' . $newNumber;

        // Buat objek Produk baru
        $products = new Produk();
        $products->idproduk = $idProduk;
        $products->namaproduk = $request->input('namaproduk');
        $products->jenisproduk = $request->input('jenisproduk');
        $products->value = $request->input('value');
        // Simpan data ke database
        $products->save();

        // Berikan response JSON
        return response()->json(['message' => 'Produk berhasil ditambahkan', 'data' => $products], 201);
    }
}
