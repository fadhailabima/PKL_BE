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
        // Simpan data ke database
        $products->save();

        // Berikan response JSON
        return response()->json(['message' => 'Produk berhasil ditambahkan', 'data' => $products], 201);
    }
    // public function tambahRak(Request $request)
    // {
    //     // Validasi request menggunakan Laravel Validator
    //     $validator = Validator::make($request->all(), [
    //         'kapasitas' => 'required|numeric',
    //         'status' => 'nullable|in:tersedia,tidak tersedia',
    //     ]);

    //     // Jika validasi gagal, kembalikan response error
    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 400);
    //     }

    //     // Dapatkan ID rak terbaru
    //     $latestRak = Rak::orderBy('idrak', 'desc')->first();

    //     // Mendapatkan angka terakhir dari ID rak terbaru
    //     $lastNumber = $latestRak ? intval(substr($latestRak->idrak, 1)) : 0;

    //     // Increment angka dan format dengan nol di depan
    //     $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

    //     // Membuat ID rak baru
    //     $idRak = 'R' . $newNumber;

    //     // Buat objek Rak baru
    //     $rak = new Rak();
    //     $rak->idrak = $idRak;
    //     $rak->kapasitas = $request->input('kapasitas');
    //     $rak->kapasitas_sisa = $request->input('kapasitas');
    //     $rak->status = $request->input('status');
    //     // Simpan data ke database
    //     $rak->save();

    //     // Berikan response JSON
    //     return response()->json(['message' => 'Rak berhasil ditambahkan', 'data' => $rak], 201);
    // }

    // public function tambahRakslot(Request $request)
    // {
    //     // Validasi request menggunakan Laravel Validator
    //     $validator = Validator::make($request->all(), [
    //         'id_rak' => 'required',
    //         'Xcoordinate' => 'required',
    //         'Ycoordinate' => 'required',
    //         'Zcoordinate' => 'required',
    //         'status' => 'nullable|in:tersedia,tidak tersedia',
    //     ]);

    //     // Jika validasi gagal, kembalikan response error
    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 400);
    //     }

    //     // Temukan rak berdasarkan id_rak dari request
    //     $rak = Rak::find($request->input('id_rak'));

    //     // Jika rak tidak ditemukan, kembalikan response error
    //     if (!$rak) {
    //         return response()->json(['errors' => ['id_rak' => 'Rak tidak ditemukan']], 404);
    //     }

    //     // Periksa apakah kapasitas maksimal rak sudah tercapai
    //     $kapasitasMaksimal = $rak->kapasitas;
    //     $jumlahRakslotSaatIni = $rak->RakSlot()->count();

    //     if ($jumlahRakslotSaatIni >= $kapasitasMaksimal) {
    //         return response()->json(['errors' => ['kapasitas' => 'Kapasitas maksimal rak sudah tercapai']], 400);
    //     }

    //     // Buat objek Rakslot baru dengan id_rakslot yang di-generate
    //     $nextIdRakslot = $this->generateNextIdRakslot($request->input('id_rak'));
    //     $rakslot = new RakSlot();
    //     $rakslot->id_rakslot = $nextIdRakslot;
    //     $rakslot->Xcoordinate = $request->input('Xcoordinate');
    //     $rakslot->Ycoordinate = $request->input('Ycoordinate');
    //     $rakslot->Zcoordinate = $request->input('Zcoordinate');
    //     $rakslot->status = $request->input('status');
    //     // Simpan relasi dengan rak
    //     $rak->RakSlot()->save($rakslot);

    //     // Berikan response JSON
    //     return response()->json(['message' => 'Rakslot berhasil ditambahkan', 'data' => $rakslot], 201);
    // }

    public function tambahTransaksi(Request $request)
    {
        // Validasi input dari formulir
        $request->validate([
            'nama_produk' => 'required|string',
            'id_rak' => 'required|string',
            'id_slotrak' => 'required|string',
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

        // Mencari id_produk berdasarkan nama_produk
        $product = Produk::where('namaproduk', $request->input('nama_produk'))->first();

        $rak = Rak::find($request->input('id_rak'));

        // Cek apakah rak ditemukan
        if (!$rak) {
            return response()->json(['error' => 'Rak tidak ditemukan.'], 404);
        }

        if ($request->input('jenis_transaksi') === 'masuk') {
            $amount = $request->input('jumlah');

            // Check if the transaction amount exceeds the remaining capacity of the rack
            if ($amount > $rak->kapasitas_sisa) {
                return response()->json(['error' => 'Jumlah transaksi melebihi kapasitas sisa rak.'], 400);
            }

            $rak->kapasitas_sisa -= $amount;

            // Mengupdate status di rakslot menjadi tidak tersedia
            $this->updateRakslotStatus($request->input('id_slotrak'), 'tidak tersedia');
        } elseif ($request->input('jenis_transaksi') === 'keluar') {
            $rak->kapasitas_sisa += $request->input('jumlah');
            // Mengupdate status di rakslot menjadi tersedia
            $this->updateRakslotStatus($request->input('id_slotrak'), 'tersedia');
        }

        // Cek apakah produk ditemukan
        if (!$product) {
            return response()->json(['error' => 'Produk tidak ditemukan.'], 404);
        }

        // Membuat transaksi baru
        $transaction = new Transaksi();
        $transaction->receiptID = $this->generateReceiptID();
        $transaction->id_produk = $product->idproduk;
        $transaction->id_rak = $request->input('id_rak');
        $transaction->id_slotrak = $request->input('id_slotrak');
        $transaction->id_karyawan = $karyawan->idkaryawan;
        $transaction->jumlah = $request->input('jumlah');
        $transaction->tanggal_transaksi = $tanggal_transaksi;
        $transaction->jenis_transaksi = $request->input('jenis_transaksi');

        // Simpan transaksi ke database
        $transaction->save();


        // Simpan perubahan kapasitas_sisa ke database
        $rak->save();

        // Memberikan respons JSON
        return response()->json(['message' => 'Transaksi berhasil ditambahkan!', 'transaction' => $transaction], 201);
    }

    // Fungsi untuk mengupdate status di rakslot
    private function updateRakslotStatus($idSlotRak, $status)
    {
        $rakslot = Rakslot::find($idSlotRak);

        // Cek apakah rakslot ditemukan
        if ($rakslot) {
            $rakslot->status = $status;
            $rakslot->save();
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
