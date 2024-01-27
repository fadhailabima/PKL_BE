<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Rak;
use App\Models\RakSlot;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreateController extends Controller
{
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

    public function tambahTransaksi(Request $request)
    {
        // Validasi input dari formulir
        $request->validate([
            'nama_produk' => 'required|string',
            'id_rak' => 'required|string',
            'jumlah' => 'required|integer',
            'jenis_transaksi' => 'required|in:masuk,keluar',
        ]);

        // Mendapatkan id_karyawan dari pengguna yang sedang login
        $user_id = Auth::id();

        // Mencari id_karyawan berdasarkan user_id
        $karyawan = DB::table('karyawans')
            ->where('user_id', $user_id)
            ->select('idkaryawan')
            ->first();

        // Pastikan id_karyawan valid
        if (!$karyawan) {
            return response()->json(['error' => 'Karyawan tidak ditemukan.'], 404);
        }

        // Mendapatkan tanggal sekarang
        $tanggal_transaksi = now()->toDateString();

        // Mencari produk berdasarkan nama_produk
        $product = Produk::where('namaproduk', $request->input('nama_produk'))->first();

        // Gantilah bagian pencarian rak dengan menggunakan relasi
        $rak = Rak::find($request->input('id_rak'));

        // Cek apakah rak ditemukan
        if (!$rak) {
            return response()->json(['error' => 'Rak tidak ditemukan.'], 404);
        }

        $nilaiProduk = $product->value;

        // Cek ketersediaan rakslot
        $rakslotTersedia = $rak->rakslot()
            ->where('status', 'tersedia')
            ->orderBy('id_rakslot') // Sesuaikan dengan urutan yang sesuai
            ->take($request->input('jumlah') * $nilaiProduk)
            ->get();

        if ($rakslotTersedia->count() < $request->input('jumlah') * $nilaiProduk) {
            return response()->json(['error' => 'Jumlah rakslot yang tersedia tidak mencukupi.'], 400);
        }

        $rakslotIds = $rakslotTersedia->pluck('id_rakslot')->toArray();

        // ...

        // Membuat transaksi baru
        $transaction = new Transaksi();
        $transaction->receiptID = $this->generateReceiptID();
        $transaction->id_produk = $product->idproduk;
        $transaction->id_rak = $request->input('id_rak');
        // $transaction->id_slotrak = $nextRakslotId; // Menggunakan ID rakslot yang baru dibuat
        $transaction->id_karyawan = $karyawan->idkaryawan;
        $transaction->jumlah = $request->input('jumlah');
        $transaction->tanggal_transaksi = $tanggal_transaksi;
        $transaction->jenis_transaksi = $request->input('jenis_transaksi');

        // Simpan transaksi ke database
        $transaction->save();

        $this->updateRakslotProductName($rakslotIds, $request->input('nama_produk'));

        // Simpan perubahan kapasitas_sisa ke database
        $rak->kapasitas_sisa = ($request->input('jenis_transaksi') === 'masuk')
            ? $rak->kapasitas_sisa - ($request->input('jumlah') * $nilaiProduk)
            : $rak->kapasitas_sisa + ($request->input('jumlah') * $nilaiProduk);

        $rak->save();

        // Memberikan respons JSON
        return response()->json(['message' => 'Transaksi berhasil ditambahkan!', 'transaction' => $transaction], 201);
    }

    private function updateRakslotProductName($idSlotRak, $namaProduk)
    {
        foreach ($idSlotRak as $idRakSlot) {
            $rakslot = RakSlot::find($idRakSlot);

            // Cek apakah rakslot ditemukan dan tersedia
            if ($rakslot && $rakslot->status == 'tersedia') {
                $rakslot->nama_produk = $namaProduk;
                $rakslot->status = 'tidak tersedia';
                $rakslot->save();
            }
        }
    }

    private function generateReceiptID()
    {
        // Mendapatkan transaksi terakhir
        $lastTransaction = Transaksi::orderByDesc('created_at')->first();

        // Jika transaksi sudah ada, ambil angka dari receiptID terakhir
        // Jika tidak, mulai dari angka 1
        $lastNumber = $lastTransaction ? intval(substr($lastTransaction->receiptID, 1)) + 1 : 1;

        // Format angka dengan nol di depan (3 digit)
        $formattedNumber = str_pad($lastNumber, 3, '0', STR_PAD_LEFT);

        // Kombinasi receiptID baru
        $newReceiptID = 'T' . $formattedNumber;

        return $newReceiptID;
    }
}
