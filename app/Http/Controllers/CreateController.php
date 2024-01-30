<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Rak;
use App\Models\RakSlot;
use App\Models\Transaksi;
use App\Models\TransaksiReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            'jumlah' => 'required|integer',
            'tanggal_expired' => 'required|date',
            'kode_produksi' => 'required|string',
            'jenis_transaksi' => 'required|in:masuk,keluar'
        ]);

        if ($request->input('jenis_transaksi') !== 'masuk') {
            return response()->json(['error' => 'Jenis transaksi harus "masuk".'], 400);
        }

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

        $lastTransaction = Transaksi::orderBy('receiptID', 'desc')->first();

        // Jika transaksi sudah ada, ambil angka dari receiptID terakhir
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->receiptID, 1));
        } else {
            // Jika tidak ada transaksi, atur lastNumber ke 0 atau nilai default
            $lastNumber = 0;
        }

        // Format angka dengan nol di depan (3 digit)
        $formattedNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        $receiptID = 'T' . $formattedNumber;

        // Mencari produk berdasarkan nama_produk
        $product = Produk::where('namaproduk', $request->input('nama_produk'))->first();
        // Log::info('Last transaction: ' . json_encode($lastTransaction));

        // Membuat transaksi baru
        $transaction = new Transaksi();
        $transaction->receiptID = $receiptID;
        $transaction->id_produk = $product->idproduk;
        $transaction->id_karyawan = $karyawan->idkaryawan;
        $transaction->jumlah = $request->input('jumlah');
        $transaction->tanggal_expired = $request->input('tanggal_expired');
        $transaction->kode_produksi = $request->input('kode_produksi');
        $transaction->tanggal_transaksi = $tanggal_transaksi;
        $transaction->jenis_transaksi = $request->input('jenis_transaksi');

        // Simpan transaksi ke database
        $transaction->save();

        // Pastikan receiptID yang Anda masukkan ke TransaksiReport sudah ada di tabel transaksis
        if (!Transaksi::where('receiptID', $transaction->receiptID)->exists()) {
            return response()->json(['error' => 'receiptID tidak valid.'], 400);
        }

        // Contoh logika untuk menentukan id_rak dan id_rakslot
        try {
            $transaction = Transaksi::findOrFail($transaction->receiptID);

            // Pastikan bahwa transaksi dengan ID tersebut ditemukan
            if (!$transaction) {
                return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
            }

            $rak = Rak::where('status', 'tersedia')->first();

            $nilaiProduk = $product->value;

            // Pastikan rak tersedia sebelum melanjutkan
            if ($rak) {
                $jumlah_transaksi = $request->input('jumlah') * $nilaiProduk;

                // Loop sampai semua jumlah transaksi terpenuhi
                while ($jumlah_transaksi > 0) {
                    // Dapatkan rakslot yang tersedia pada rak yang dipilih
                    $rakslot = RakSlot::where('id_rak', $rak->idrak)
                        ->where('status', 'tersedia')
                        ->first();

                    // Pastikan rakslot tersedia sebelum melanjutkan
                    if (!$rakslot) {
                        return response()->json(['error' => 'Tidak ada rakslot yang tersedia pada rak yang dipilih.'], 404);
                    }

                    // Hitung jumlah transaksi yang dapat dimasukkan ke dalam rakslot saat ini
                    $jumlah_transaksi_rakslot = min($jumlah_transaksi, $rakslot->kapasitas);

                    // Logika transaksi_report
                    $transaksi_report = new TransaksiReport();
                    $transaksi_report->receiptID = $transaction->receiptID; // Pastikan receiptID valid
                    $transaksi_report->id_rak = $rak->idrak; // Pastikan id_rak valid
                    $transaksi_report->id_rakslot = $rakslot->id_rakslot; // Pastikan id_rakslot valid
                    $transaksi_report->jumlah = $jumlah_transaksi_rakslot;
                    $transaksi_report->tanggal_transaksi = $transaction->tanggal_transaksi;
                    $transaksi_report->nama_produk = $transaction->produk->namaproduk;
                    $transaksi_report->jenis_transaksi = $transaction->jenis_transaksi;

                    // Simpan transaksi_report ke database
                    $transaksi_report->save();

                    // Kurangi jumlah transaksi dan kapasitas yang tersedia
                    $jumlah_transaksi -= $jumlah_transaksi_rakslot;

                    // Update kapasitas rakslot
                    $rakslot->kapasitas -= $jumlah_transaksi_rakslot;
                    $rakslot->save();

                    // Jika masih ada transaksi dan kapasitas rakslot sudah habis, pindah ke rakslot berikutnya
                    if ($jumlah_transaksi > 0 && $rakslot->kapasitas <= 0) {
                        // Update status rakslot menjadi tidak tersedia
                        $rakslot->status = 'tidak tersedia';
                        $rakslot->save();

                        // Cari rakslot selanjutnya pada rak yang sama
                        $nextRakSlot = RakSlot::where('id_rak', $rak->idrak)
                            ->where('status', 'tersedia')
                            ->where('id_rakslot', '>', $rakslot->id_rakslot)
                            ->first();

                        if (!$nextRakSlot) {
                            return response()->json(['error' => 'Tidak ada rakslot yang tersedia pada rak yang dipilih.'], 404);
                        }

                        // Gunakan rakslot pada rak yang sama
                        $rakslot = $nextRakSlot;
                    }
                }

                // Memberikan respons JSON
                return response()->json(['message' => 'Transaksi berhasil ditambahkan!', 'transaction' => $transaction], 201);
            } else {
                return response()->json(['error' => 'Tidak ada rak yang tersedia.'], 404);
            }
        } catch (\Exception $e) {
            // Tangkap pesan error dan kirimkan sebagai respons JSON
            return response()->json(['error' => 'Gagal menambahkan TransaksiReport: ' . $e->getMessage()], 500);
        }
    }

    public function transaksiKeluar(Request $request)
    {
        // Validasi input dari formulir
        $request->validate([
            'nama_produk' => 'required|string',
            'jumlah' => 'required|integer',
            'jenis_transaksi' => 'required|in:masuk,keluar'
        ]);

        if ($request->input('jenis_transaksi') !== 'keluar') {
            return response()->json(['error' => 'Jenis transaksi harus "keluar".'], 400);
        }

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

        $lastTransaction = Transaksi::orderBy('receiptID', 'desc')->first();

        // Jika transaksi sudah ada, ambil angka dari receiptID terakhir
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->receiptID, 1));
        } else {
            // Jika tidak ada transaksi, atur lastNumber ke 0 atau nilai default
            $lastNumber = 0;
        }

        // Format angka dengan nol di depan (3 digit)
        $formattedNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        $receiptID = 'T' . $formattedNumber;

        // Mencari produk berdasarkan nama_produk
        $product = Produk::where('namaproduk', $request->input('nama_produk'))->first();

        // Mencari transaksi masuk yang sesuai
        $transaksi_masuk = TransaksiReport::where('nama_produk', $request->input('nama_produk'))
            ->where('jenis_transaksi', 'masuk')
            ->where('jumlah', '>', 0) // Pastikan hanya mempertimbangkan transaksi masuk
            ->get();

        // Jika tidak ada transaksi masuk, berikan pesan kesalahan
        if ($transaksi_masuk->isEmpty()) {
            return response()->json(['error' => 'Tidak ada stok barang yang diminta.'], 400);
        }

        // Membuat transaksi baru
        $transaction = new Transaksi();
        $transaction->receiptID = $receiptID;
        $transaction->id_produk = $product->idproduk;
        $transaction->id_karyawan = $karyawan->idkaryawan;
        $transaction->jumlah = $request->input('jumlah');
        $transaction->tanggal_transaksi = $tanggal_transaksi;
        $transaction->jenis_transaksi = $request->input('jenis_transaksi');
        $transaction->save();

        if (!Transaksi::where('receiptID', $transaction->receiptID)->exists()) {
            return response()->json(['error' => 'receiptID tidak valid.'], 400);
        }

        try {
            $transaction = Transaksi::findOrFail($transaction->receiptID);

            // Pastikan bahwa transaksi dengan ID tersebut ditemukan
            if (!$transaction) {
                return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
            }

            $rak = Rak::where('status', 'tersedia')->first();

            $nilaiProduk = $product->value;

            // Pastikan rak tersedia sebelum melanjutkan
            if ($rak) {
                $jumlah_transaksi = $request->input('jumlah') * $nilaiProduk;

                // Ambil semua rakslot yang memiliki produk yang ingin dikeluarkan
                $rakslot = TransaksiReport::where('nama_produk', $transaction->id_produk)
                    ->where('jenis_transaksi', 'masuk') // Hanya transaksi masuk
                    ->orderBy('tanggal_transaksi', 'asc')
                    // ->select('id_rak', 'id_rakslot')
                    ->get();

                dd($rakslot->toSql(), $rakslot->getBindings());

                // Logika transaksi_report untuk transaksi keluar
                $transaksi_report_keluar = new TransaksiReport();
                $transaksi_report_keluar->receiptID = $transaction->receiptID;
                $transaksi_report_keluar->id_rak = $rakslot->id_rak;
                $transaksi_report_keluar->id_rakslot = $rakslot->id_rakslot;
                // Hitung jumlah transaksi yang dapat dimasukkan ke dalam rakslot saat ini
                $jumlah_transaksi_rakslot = min($jumlah_transaksi, $rakslot->kapasitas);

                $transaksi_report_keluar->jumlah = -$jumlah_transaksi_rakslot; // Jumlah negatif karena transaksi keluar
                $transaksi_report_keluar->jenis_transaksi = $transaction->jenis_transaksi;
                $transaksi_report_keluar->tanggal_transaksi = $transaction->tanggal_transaksi;
                $transaksi_report_keluar->nama_produk = $transaction->produk->namaproduk;
                $transaksi_report_keluar->save();

                $rakslotToUpdate = RakSlot::find($rakslot->id_rakslot);

                // Pastikan rakslot ditemukan
                if (!$rakslotToUpdate) {
                    return response()->json(['error' => 'Rakslot tidak ditemukan.'], 404);
                }
                // Update kapasitas rakslot
                $new_kapasitas = $rakslotToUpdate->kapasitas - $jumlah_transaksi_rakslot;

                // Pastikan kapasitas tidak kurang dari 0
                $rakslotToUpdate->kapasitas = max($new_kapasitas, 0);

                $rakslotToUpdate->save();

                // Kurangi jumlah transaksi yang telah diakomodasi oleh rakslot
                $jumlah_transaksi -= $jumlah_transaksi_rakslot;

                // Jika masih ada transaksi dan kapasitas rakslot awal sudah habis, pindah ke rakslot lain yang memiliki barang
                while ($transaction->jumlah > 0) {
                    $rakslot_lain = TransaksiReport::where('nama_produk', $transaction->id_produk)
                        ->where('jenis_transaksi', 'masuk')
                        ->where('jumlah', '>', 0)
                        ->where('id_rak', '!=', $rakslot->id_rak) // Exclude rakslot awal
                        ->orderBy('tanggal_transaksi', 'asc')
                        ->select('id_rak', 'id_rakslot')
                        ->first();

                    if (!$rakslot_lain) {
                        // Tidak ada rakslot lain yang memiliki barang, keluar dari loop
                        break;
                    }

                    // Logika transaksi_report untuk transaksi keluar dari rakslot lain
                    $transaksi_report_keluar_lain = new TransaksiReport();
                    $transaksi_report_keluar_lain->receiptID = $transaction->receiptID;
                    $transaksi_report_keluar_lain->id_rak = $rakslot_lain->id_rak;
                    $transaksi_report_keluar_lain->id_rakslot = $rakslot_lain->id_rakslot;

                    // Hitung jumlah transaksi yang dapat dimasukkan ke dalam rakslot lain
                    $jumlah_transaksi_rakslot_lain = min($transaction->jumlah, 100);

                    $transaksi_report_keluar_lain->jumlah = -$jumlah_transaksi_rakslot_lain;
                    $transaksi_report_keluar_lain->jenis_transaksi = $transaction->jenis_transaksi;
                    $transaksi_report_keluar_lain->tanggal_transaksi = $transaction->tanggal_transaksi;
                    $transaksi_report_keluar_lain->nama_produk = $transaction->produk->namaproduk;
                    $transaksi_report_keluar_lain->save();

                    // Kurangi jumlah transaksi yang telah diakomodasi oleh rakslot lain
                    $transaction->jumlah -= $jumlah_transaksi_rakslot_lain;
                }

                // Memberikan respons JSON
                return response()->json(['message' => 'Transaksi berhasil ditambahkan!', 'transaction' => $transaction], 201);
            } else {
                return response()->json(['error' => 'Tidak ada rak yang tersedia.'], 404);
            }
        } catch (\Exception $e) {
            // Tangkap pesan error dan kirimkan sebagai respons JSON
            Log::error('Gagal menambahkan TransaksiReport: ' . $e->getMessage());
            Log::error('Detail TransaksiReport:', [
                'receiptID' => $transaction->receiptID ?? null,
                'id_produk' => $transaction->id_produk ?? null,
                'id_rak' => $rakslot->id_rak ?? null,
                'id_rakslot' => $rakslot->id_rakslot ?? null,
                'id_karyawan' => $transaction->id_karyawan ?? null,
                'jumlah' => $transaction->jumlah ?? null,
                'tanggal_transaksi' => $transaction->tanggal_transaksi ?? null,
                'jenis_transaksi' => $transaction->jenis_transaksi ?? null,
            ]);

            return response()->json(['error' => 'Gagal menambahkan TransaksiReport.'], 500);
        }
    }
}
