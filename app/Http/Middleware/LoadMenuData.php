<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RoleRuta;
use Illuminate\Support\Facades\DB;
class LoadMenuData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
                // Habilitar el log de consultas
                // DB::enableQueryLog();
        // Obtenemos el ID del rol de la sesión, que será null si RoleID no existe
        // $roleId = $request->session()->get('RoleID');
        $roleId = $request->session()->get('RoleID', 'null');

        // dd($roleId);
        $menus = RoleRuta::where('rolMenu', 'LIKE', '%' . $roleId . '%')
            ->whereNotNull('nmenu')
            ->orderBy('orden')
            ->get()
            ->groupBy('grupo');
    $modulos = DB::select('EXEC dbo.getCatalogoModulos');
    // dd($modulos);
    $modulosArray = [];
    foreach ($modulos as $modulo) {
        $modulosArray[strtolower($modulo->Nombre)] = $modulo->ModuloId;
    }
    
    foreach ($menus as $group => &$items) {
        foreach ($items as &$item) {
            // Usamos null como valor predeterminado en caso de que no encontremos un emparejamiento
            $item->ModuloId = $modulosArray[strtolower($item->nmenu)] ?? null;
        }
    }
    
// dd($menus);        
    
    
    
            // Imprimir la consulta SQL cruda y los parámetros
            // $queries = DB::getQueryLog();
            // $last_query = end($queries);
    
            // // Esto imprimirá la consulta y los parámetros en el error log
            // error_log(json_encode($last_query));
        // Compartimos los menús con todas las vistas
        view()->share('menus', $menus);
    
        return $next($request);
    }
    
    
}
