<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionService
{
     /**
     * Obtiene la duración de la sesión para un usuario según su RoleID.
     *
     * @param  Request  $request
     * @return int
     */
    public function getSessionLifetime($request)
    {
        $roleId = session('RoleID');
        
        // Si RoleID es null o 'null', devolver 60
        if ($roleId === null || $roleId === 'null') {
            return 60;
        }
    
        // Obtiene el tiempo de sesión del role desde la base de datos
        $role = DB::table('CatalogoRoles')->where('ID_CatalogoRoles', $roleId)->first();
    
        if ($role) {
            // dd("Role: ", $role);
            // dd("Tiempo de sesión: ", $role->TiempoSesion);
            // Si encontró el role, devuelve su tiempo de sesión
            return $role->TiempoSesion;
        } else {
            // Si no encontró el role, devuelve la configuración predeterminada
            return config('session.lifetime');
        }
    }
        /**
     * Actualiza la hora de la última actividad en la sesión.
     *
     * @param  Request  $request
     */
    public function updateLastActivity($request)
    {
        $request->session()->put('lastActivity', time());
    }
}
