<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SessionTimeout
{
        /**
     * Maneja el middleware de tiempo de sesión.
     *
     *   \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (Auth::guard('web')->check() || Auth::guard('store')->check()) {
            if ($this->sessionExpired($request)) {

                Auth::logout();
                if ($this->sessionExpired($request)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    Session::flush();
                    return redirect('login')->with('alert', 'Tu sesión ha caducado debido a la inactividad');
                } else {
                    $this->updateSessionTime($request);
                }
                
                Session::flush();
                return redirect('login')->with('alert', 'Tu sesión ha caducado debido a la inactividad');
            } else {
                $this->updateSessionTime($request);
            }
        }
        return $next($request);
    }
    /**
     * Verifica si la sesión ha caducado debido a la inactividad.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function sessionExpired($request)
    {
        $sessionLifeTime = $this->getSessionLifetime($request) * 60; // en segundos
    
        $isSessionExpired = (time() - $request->session()->get('lastActivity')) > $sessionLifeTime;
    
        // Log::info('Is session expired: ' . ($isSessionExpired ? 'Yes' : 'No'));
        // Log::info('Session lifetime (seconds): ' . $sessionLifeTime);
        // Log::info('Last activity time: ' . $request->session()->get('lastActivity'));
    
        if ($isSessionExpired) {
            return true;
        }
    
        return false;
    }
    
    /**
     * Obtiene la duración de la sesión para un usuario según su RoleID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function getSessionLifetime($request)
    {
        $roleId = session('RoleID');
    
        // Si RoleID es null o 'null', devolver 60
        if ($roleId === null || $roleId === 'null') {
            return 60;
        }
    
        // Obtiene el tiempo de sesión del role desde la base de datos
        $role = DB::table('CatalogoRoles')->where('ID_CatalogoRoles', $roleId)->first();
    
        if ($role) {
            // Si encontró el role, devuelve su tiempo de sesión
            return $role->TiempoSesion;
        } else {
            // Si no encontró el role, devuelve la configuración predeterminada
            return config('session.lifetime');
        }
    }

    /**
     * Actualiza el tiempo de última actividad de la sesión.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function updateSessionTime($request)
    {
        $request->session()->put('lastActivity', time());
        // Log::info('Updated lastActivity time: ' . $request->session()->get('lastActivity'));

    }
}
