<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $adminUser = User::create([
            'username' => '12345',
            'password' => Hash::make('saprotan'), // replace 'password' with the real password
            'level' => 'admin',
        ]);

        Admin::create([
            'idadmin' => $adminUser->username,
            'nama' => 'Fadhail A Bima', // replace with the real name
            'user_id' => $adminUser->id,
        ]);

        // Create karyawan user
        // $karyawanUser = User::create([
        //     'username' => '12345',
        //     'password' => Hash::make('saprotan'), // replace 'password' with the real password
        //     'level' => 'karyawan',
        // ]);

        // Karyawan::create([
        //     'idkaryawan' => $karyawanUser->username,
        //     'nama' => 'Fadhail A Bima', // replace with the real name
        //     'user_id' => $karyawanUser->id,
        // ]);
    }
}
