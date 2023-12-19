<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Services\UserService; // Asegúrate de tener la ruta correcta
use Illuminate\Support\Str;



class CheckRoutePermission
{
    protected $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }
    public function handle($request, Closure $next)
    {
        
        // $listener = Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
        //     dump($event->sql, $event->bindings);
        // });
        $userRole = Auth::guard('store')->user()->RoleID ?? '0'; // Asegúrate de tener la columna 'role' en tu tabla 'users' o 'admins'
        $userRole = null;

        // Comprueba el guardia 'web'
        if (Auth::guard('web')->check()) {
            
            $userRole = Auth::guard('web')->user()->RoleID;
        }

        // Si no se encontró un usuario, comprueba el guardia 'admin'
        if (!$userRole && Auth::guard('store')->check()) {
            $userRole = Auth::guard('store')->user()->RoleID ?? 'null';
        }
        // $userRole = Auth::user()->RoleID ?? 'null'; // Asegúrate de tener la columna 'role' en tu tabla 'users' o 'admins'
        // dd(Auth::user());
        // dump(Auth::guard('web')->check(), Auth::guard('store')->check());
        // Auth::user()->update($user->toArray());
        
        // Log::info('User RoleID Middleware: ' . $userRole);

        $roleID = session('RoleID') ?? 'null';
        $rutaModulo = session('module') ?? 'home';
        $routeName = $request->route()->uri();
        // dd($routeName, $rutaModulo, $roleID, $userRole);
        // Verifica si la ruta es '/home/aplicaciones/prestamosDirectos/updateSessionTime'
        if ($routeName === 'home/aplicaciones/prestamosDirectos/updateSessionTime') {
            // Si es así, permite la petición sin verificación de permisos
            return $next($request);
        }
        // dd($rutaModulo);
        // dd($routeName, $rutaModulo, $roleID, $userRole);
        if (Str::contains($rutaModulo, 'home')) {
    // dd("if 1");
        // dd($routeName, $rutaModulo, $roleID, $userRole);

            if(Str::length($rutaModulo) <= 4){
// dd("if 2");
            }else{
                // dd("if 3");
                if (Str::contains($routeName, $rutaModulo) ) {
                    $routeName = $rutaModulo;
                    // dd("if 4");
                } else {
                    // dd("if 5");
                    $request->session()->put('module','inicio');
                    return redirect('home');  
        
                }
            }
        }else{
        // dd($routeName, $rutaModulo, $roleID, $userRole);

            // dd("if 6");
            // dd($routeName, $rutaModulo);
            if (Str::contains($routeName, [$rutaModulo, 'secondScreen'])) {
        // dd($routeName, $rutaModulo, $roleID, $userRole);
               if(Str::contains($routeName, 'secondScreen')){

               }else{
                $routeName = $rutaModulo;
               }
            } else {

                // dd("no tiene permiso para acceder a esta ruta");
                session()->put('RoleID', 'null');
                $roleID = 'null';
            }
        }

        // Escucha el evento QueryExecuted
        
// dd($routeName, $roleID);
        $permission = DB::table('RoleRutas')
            ->where('ruta', 'like', '%' . $routeName . '%')
            ->where('roles','like', '%' . $roleID . '%')
            ->where('estatus', 1)
            ->first();
// dd($permission);
// Event::forget($listener);
        if ($permission) {

            return $next($request);
        }

        // dd("no tiene permiso para acceder a esta ruta");
        // Si el usuario no tiene permiso para acceder a la ruta, redirígelo a donde quieras.
        return redirect('home');  
    }
    
    public function terminate($request, $response)
    {
        // Este método se llamará después de que la respuesta haya sido enviada al cliente...
        
$rutita = null;
// log::info('ME ESTOY EJECUTANDO AL FINAL');
}
}

