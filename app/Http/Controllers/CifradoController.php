<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CifradoController extends Controller
{
    public function cifrar(Request $request) {
        $texto = $request->input('texto');
        $cifrado = Crypt::encryptString($texto);
        return response()->json(['cifrado' => $cifrado]);
    }
}
