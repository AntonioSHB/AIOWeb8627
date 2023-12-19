<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class PasswordUpdateService
{
    public function updatePassword($userType, $userId, $newPassword)
    {
        // Encriptar la contraseña
        // $hashedPassword = Hash::make($newPassword);
        $hashedPassword = hash('sha3-256', $newPassword);

        // Ejecutar el procedimiento almacenado
        $results = DB::select('exec [dbo].[ActualizarClave] ?, ?, ?', [$userType, $userId, $hashedPassword]);

        Log::info($results);
        // Devolver los resultados
        return $results;
    }
}
