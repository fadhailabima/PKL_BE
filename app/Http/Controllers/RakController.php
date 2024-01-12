<?php

namespace App\Http\Controllers;

use App\Models\Rak;
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
}
