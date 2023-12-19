<?php

namespace App\Http\Controllers\Auth;

use App\Services\PasswordUpdateService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class PasswordUpdateController extends Controller
{
    protected $passwordUpdateService;

    public function __construct(PasswordUpdateService $passwordUpdateService)
    {
        $this->passwordUpdateService = $passwordUpdateService;
    }
    public function index()
    {
        $data["title"] = "Renovar Contraseña.";

        return view('auth.renewPassword')->with($data);
    }
    public function update(Request $request)
    {
        // Recoger los datos del formulario

        $userId = $request->input('usuarioID');
        $newPassword = $request->input('password');
        $userType = $request->input('tipo');
        // $userType = ($userTypeInput == 'user') ? '1' : '2';
        // dd($userType, $userId, $newPassword, $userTypeInput);
        // Registro en el log
        $logData = [
            'userType' => $userType, 
            'userId' => $userId, 
            'newPassword' => $newPassword
        ];
        // dd($logData);
        // Log::info('Updating password', $logData);  // <- Aquí está el cambio

        // Actualizar la contraseña
        $results = $this->passwordUpdateService->updatePassword($userType, $userId, $newPassword);
        // log::info("Resultado del servicio:",$results);
        // dd($results);
        // Analizar los resultados y devolver una respuesta apropiada
        if ($results[0]->status == 1) {
            return response()->json(['status' => true, 'message' => 'Se ha cambiado la contraseña correctamente.']);
        } elseif ($results[0]->msn == 'No puede repetir contraseñas anteriores.') {
            return response()->json(['status' => false, 'message' => 'No se puede repetir la contraseña de las últimas 5 proporcionadas']);
        } elseif ($results[0]->msn == 'No fue posible cambiar la contraseña.') {
            return response()->json(['status' => false, 'message' => 'No fue posible cambiar la contraseña, favor de intentarlo de nuevo']);
        } else {
            return response()->json(['status' => false, 'message' => 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.']);
        }
    }
}
