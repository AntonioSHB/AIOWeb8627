<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoleService
{
    public function getRoutesByRole($roleId,$module) {
        // Comprueba si roleId es null. Si es así, usa un valor predeterminado.
        $roleId = $roleId ?? 'null';
        // dd($roleId);
        $routes = DB::table('RoleRutas')
                ->where('estatus', 1)
                ->where('roles', 'like', '%' . $roleId . '%')
                ->where('ruta', 'like', '%' . $module . '%')
                ->pluck('ruta')
                ->toArray();
        // dd($routes);
        return $routes;
    }
}
