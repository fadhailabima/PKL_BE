<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Produk;
use App\Models\Rak;
use App\Models\RakSlot;
use App\Models\Transaksi;
use App\Models\TransaksiReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    public function getAllTransaksi()
    {
        $transaksis = Transaksi::with('karyawan', 'produk')->get();

        return response()->json(['transaksis' => $transaksis]);
    }

    public function tambahTransaksi(Request $request)
    {
        // Validasi input dari formulir

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
        $transaction->jenis_transaksi = 'masuk';

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
                    $jumlah_transaksi_rakslot = min($jumlah_transaksi, $rakslot->kapasitas_maksimal - $rakslot->kapasitas_terpakai);

                    // Logika transaksi_report
                    $transaksi_report = new TransaksiReport();
                    $transaksi_report->receiptID = $transaction->receiptID; // Pastikan receiptID valid
                    $transaksi_report->id_rak = $rak->idrak; // Pastikan id_rak valid
                    $transaksi_report->id_rakslot = $rakslot->id_rakslot; // Pastikan id_rakslot valid
                    $transaksi_report->jumlah = $jumlah_transaksi_rakslot;
                    $transaksi_report->expired_date = $transaction->tanggal_expired;
                    $transaksi_report->nama_produk = $transaction->id_produk;
                    $transaksi_report->jenis_transaksi = $transaction->jenis_transaksi;

                    // Simpan transaksi_report ke database
                    $transaksi_report->save();

                    // Kurangi jumlah transaksi dan kapasitas yang tersedia
                    $jumlah_transaksi -= $jumlah_transaksi_rakslot;
                    $rakslot->kapasitas_terpakai += $jumlah_transaksi_rakslot;

                    // Pastikan kapasitas_terpakai tidak melebihi kapasitas_maksimal
                    $rakslot->kapasitas_terpakai = min($rakslot->kapasitas_terpakai, $rakslot->kapasitas_maksimal);

                    if ($rakslot->kapasitas_terpakai >= $rakslot->kapasitas_maksimal) {
                        $rakslot->status = 'tidak tersedia';
                    }

                    $rakslot->save();

                    // Jika masih ada transaksi dan kapasitas_terpakai sudah mencapai kapasitas_maksimal, pindah ke rakslot berikutnya
                    if ($jumlah_transaksi > 0 && $rakslot->kapasitas_terpakai >= $rakslot->kapasitas_maksimal) {
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

        // Membuat transaksi baru
        $transaction = new Transaksi();
        $transaction->receiptID = $receiptID;
        $transaction->id_produk = $product->idproduk;
        $transaction->id_karyawan = $karyawan->idkaryawan;
        $transaction->jumlah = $request->input('jumlah');
        $transaction->tanggal_transaksi = $tanggal_transaksi;
        $transaction->jenis_transaksi = 'keluar';
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
                $rakslotReport = TransaksiReport::where('nama_produk', $transaction->id_produk)
                    ->with([
                        'rakSlot' => function ($query) {
                            $query->select('id_rakslot', 'kapasitas_terpakai');
                            // ->where('kapasitas_terpakai', '>', 0);
                        }
                    ])
                    ->where('jenis_transaksi', 'masuk')
                    ->orderBy('expired_date', 'asc')
                    ->select('id_rak', 'id_rakslot')
                    ->get();

                // get the first rakSlotReport->rak_slot->kapasitas_terpakai > 0
                $rakslotReport = $rakslotReport->first(function ($value, $key) {
                    return $value->rakSlot->kapasitas_terpakai > 0;
                });

                if (!$rakslotReport || !$rakslotReport->id_rak || !$rakslotReport->id_rakslot) {
                    return response()->json(['error' => 'Tidak ada rakslot yang tersedia.'], 404);
                }

                if ($rakslotReport) {
                    // Dapatkan model RakSlot berdasarkan id_rak dan id_rakslot dari TransaksiReport
                    $rakslot = RakSlot::where('id_rak', $rakslotReport->id_rak)
                        ->where('id_rakslot', $rakslotReport->id_rakslot)
                        ->select('kapasitas_terpakai')
                        ->first();

                    if (!$rakslot) {
                        return response()->json(['error' => 'Rakslot tidak ditemukan.'], 404);
                    }

                    // Logika transaksi_report untuk transaksi keluar
                    $transaksi_report_keluar = new TransaksiReport();
                    $transaksi_report_keluar->receiptID = $transaction->receiptID;
                    $transaksi_report_keluar->id_rak = $rakslotReport->id_rak;
                    $transaksi_report_keluar->id_rakslot = $rakslotReport->id_rakslot;
                    // Hitung jumlah transaksi yang dapat dimasukkan ke dalam rakslot saat ini
                    $jumlah_transaksi_terpakai = min($jumlah_transaksi, $rakslot->kapasitas_terpakai);

                    $transaksi_report_keluar->jumlah = -$jumlah_transaksi_terpakai; // Jumlah negatif karena transaksi keluar
                    $transaksi_report_keluar->jenis_transaksi = $transaction->jenis_transaksi;
                    // $transaksi_report_keluar->tanggal_transaksi = $transaction->tanggal_transaksi;
                    $transaksi_report_keluar->nama_produk = $transaction->id_produk;
                    $transaksi_report_keluar->save();

                    $rakslotToUpdate = RakSlot::find($rakslotReport->id_rakslot);

                    // Pastikan rakslot ditemukan
                    if (!$rakslotToUpdate) {
                        return response()->json(['error' => 'Rakslot tidak ditemukan.', 'search_conditions' => ['id_rak' => $rakslotReport->id_rak, 'id_rakslot' => $rakslotReport->id_rakslot, 'additional_info' => 'Pastikan data Rakslot dengan kondisi pencarian tersebut tersedia.']], 404);
                    }

                    // Update kapasitas rakslot
                    $rakslotToUpdate->kapasitas_terpakai -= $jumlah_transaksi_terpakai;

                    // Pastikan kapasitas_terpakai tidak kurang dari 0
                    $rakslotToUpdate->kapasitas_terpakai = max($rakslotToUpdate->kapasitas_terpakai, 0);

                    if ($rakslotToUpdate->kapasitas_terpakai < $rakslotToUpdate->kapasitas_maksimal) {
                        $rakslotToUpdate->status = 'tersedia';
                    }

                    $rakslotToUpdate->save();

                    // Kurangi jumlah transaksi yang telah diakomodasi oleh rakslot
                    $jumlah_transaksi -= $jumlah_transaksi_terpakai;


                    // Jika masih ada transaksi dan kapasitas rakslot awal sudah habis, pindah ke rakslot lain yang memiliki barang
                    while ($jumlah_transaksi > 0) {
                        try {
                            $current_rakslot_id = $rakslotReport->id_rakslot;

                            // Cari rakslot selanjutnya yang memiliki barang tersebut
                            $rakslot_lain = TransaksiReport::where('nama_produk', $transaction->id_produk)
                                ->where('jenis_transaksi', 'masuk')
                                ->where('id_rakslot', '>', $current_rakslot_id) // Hanya pertimbangkan rakslot dengan id lebih besar
                                ->with([
                                    'rakSlot' => function ($query) {
                                        $query->select('id_rakslot', 'kapasitas_terpakai');
                                        // ->where('kapasitas_terpakai', '>', 0);
                                    }
                                ])
                                ->orderBy('expired_date', 'asc') // Urutkan berdasarkan id_rakslot
                                ->select('id_rak', 'id_rakslot') // Sertakan kapasitas_terpakai
                                ->get();

                            $rakslot_lain = $rakslot_lain->first(function ($value, $key) {
                                return $value->rakSlot->kapasitas_terpakai > 0;
                            });

                            if (!$rakslot_lain) {
                                // Tidak ada rakslot lain yang memiliki barang, keluar dari loop
                                break;
                            }

                            // Logika transaksi_report untuk transaksi keluar dari rakslot lain
                            $transaksi_report_keluar_lain = new TransaksiReport();
                            $transaksi_report_keluar_lain->receiptID = $transaction->receiptID;
                            $transaksi_report_keluar_lain->id_rak = $rakslot_lain->id_rak;
                            $transaksi_report_keluar_lain->id_rakslot = $rakslot_lain->id_rakslot;

                            // Dapatkan model RakSlot untuk rakslot lain
                            $rakslot_lain_model = RakSlot::where('id_rak', $rakslot_lain->id_rak)
                                ->where('id_rakslot', $rakslot_lain->id_rakslot)
                                ->first();

                            if (!$rakslot_lain_model) {
                                return response()->json(['error' => 'Rakslot lain tidak ditemukan.'], 404);
                            }

                            // Hitung jumlah transaksi yang dapat dimasukkan ke dalam rakslot lain
                            // $jumlah_transaksi_rakslot_lain = min($transaction->jumlah, $rakslot_lain_model->kapasitas_terpakai);
                            $jumlah_transaksi_sekarang = min($jumlah_transaksi, $rakslot_lain_model->kapasitas_terpakai);

                            $transaksi_report_keluar_lain->jumlah = -$jumlah_transaksi_sekarang;
                            $transaksi_report_keluar_lain->jenis_transaksi = $transaction->jenis_transaksi;
                            // $transaksi_report_keluar_lain->tanggal_transaksi = $transaction->tanggal_transaksi;
                            $transaksi_report_keluar_lain->nama_produk = $transaction->id_produk;
                            $transaksi_report_keluar_lain->save();

                            // Update kapasitas_terpakai pada rakslot lain
                            $rakslot_lain_model->kapasitas_terpakai -= $jumlah_transaksi_sekarang;

                            // Pastikan kapasitas_terpakai tidak kurang dari 0
                            $rakslot_lain_model->kapasitas_terpakai = max($rakslot_lain_model->kapasitas_terpakai, 0);

                            if ($rakslot_lain_model->kapasitas_terpakai < $rakslot_lain_model->kapasitas_maksimal) {
                                $rakslot_lain_model->status = 'tersedia';
                            }

                            $rakslot_lain_model->save();

                            $jumlah_transaksi -= $jumlah_transaksi_sekarang;

                        } catch (\Exception $e) {
                            echo $e->getMessage();
                        }
                    }

                    // return response()->json(['success' => ''], 200);
                    // Memberikan respons JSON
                    return response()->json(['message' => 'Transaksi berhasil ditambahkan!', 'transaction' => $transaction], 201);
                } else {
                    return response()->json(['error' => 'Tidak ada rakslot yang tersedia.'], 404);
                }
            } else {
                return response()->json(['error' => 'Tidak ada rak yang tersedia.'], 404);
            }
        } catch (\Exception $e) {
            // Tangkap pesan error dan kirimkan sebagai respons JSON
            Log::error('Gagal menambahkan TransaksiReport: ' . $e->getMessage());
            Log::error('Detail TransaksiReport:', [
                'receiptID' => $transaction->receiptID ?? null,
                'id_rak' => $rakslotReport->id_rak ?? null,
                'id_rakslot' => $rakslotReport->id_rakslot ?? null,
                'id_produk' => $transaction->id_produk ?? null,
                'id_karyawan' => $transaction->id_karyawan ?? null,
                'jumlah' => $transaction->jumlah ?? null,
                'tanggal_transaksi' => $transaction->tanggal_transaksi ?? null,
                'jenis_transaksi' => $transaction->jenis_transaksi ?? null,
            ]);

            return response()->json(['error' => 'Gagal menambahkan TransaksiReport.'], 500);
        }
    }

    public function getDetailTransaksi($receiptID)
    {
        try {
            $transaksi = TransaksiReport::where('receiptID', $receiptID)->get();

            return response()->json(['success' => true, 'data' => $transaksi], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteTransaksi($receiptID)
    {
        // Cari transaksi berdasarkan ID
        $transaksi = Transaksi::with('transaksiReports.rakSlot')->find($receiptID);

        if (!$transaksi) {
            return response()->json(['message' => 'Transaksi not found'], 404);
        }

        // Mengembalikan kapasitas_terpakai di RakSlot
        foreach ($transaksi->transaksiReports as $report) {
            if ($transaksi->jenis_transaksi == 'keluar') {
                $report->rakSlot->kapasitas_terpakai += $report->jumlah;
            } else {
                $report->rakSlot->kapasitas_terpakai -= $report->jumlah;
            }
            $report->rakSlot->save();
            $report->delete();
        }

        // Hapus transaksi
        $transaksi->delete();

        return response()->json(['message' => 'Transaksi and related reports deleted successfully']);
    }

    public function getTransaksibyKaryawan()
    {
        // Mendapatkan karyawan yang sedang login
        $user_id = Auth::id();

        // Mencari data admin berdasarkan user_id
        $karyawan = Karyawan::where('user_id', $user_id)->first();

        if (!$karyawan) {
            return response()->json(['error' => 'Karyawan tidak ditemukan.'], 404);
        }

        // Mengambil data transaksi berdasarkan ID karyawan
        $transaksi = Transaksi::where('id_karyawan', $karyawan->idkaryawan)
            ->with('produk')
            ->get();

        // Memberikan respons JSON dengan data transaksi
        return response()->json(['transaksi' => $transaksi], 200);
    }

    public function getAllTransaksiReport()
    {
        $transaksi = Transaksi::join('products', 'transaksis.id_produk', '=', 'products.idproduk')
            ->where('transaksis.jenis_transaksi', 'masuk')
            ->select('products.namaproduk') // Select namaproduk from products table
            ->distinct()
            ->get();

        return response()->json(['transaksiReport' => $transaksi]);
    }
}
