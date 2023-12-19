<?php

// app/Http/Middleware/CheckAnyAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\RoleService;
use Illuminate\Support\Str;


class CheckAnyAuth
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

public function handle($request, Closure $next)
{
    if (Auth::guard('web')->check() || Auth::guard('store')->check()) {
        $roleId = session('RoleID') ?? null;
                // dd($roleId);
        
        $module = $request->input('module');
        $allowedRoutes = $this->roleService->getRoutesByRole($roleId, $module);
        // dd($allowedRoutes);
        $accessAllowed = false;
        $currentRoute = strtolower($request->path());
        $allowedRoutes = array_map('strtolower', $allowedRoutes);
        $allowedRoutes = array_merge($allowedRoutes, ['home/authenticate']); // Agregar 'home/authenticate'
        // dd($roleId);
        Log::debug('AQUI Allowed routes for role: ' . $roleId, $allowedRoutes);
        // dd($roleId,$module,$currentRoute, $allowedRoutes);
        if (in_array($currentRoute, $allowedRoutes)) {
            $accessAllowed = true;
        }
        
        if (!$accessAllowed) {
            Log::warning('Access denied for route: ' . $currentRoute);

            if ($request->ajax()) {
                return response('Access denied.', 403);
            } else {
                return redirect('/home');
            }
        }

        $response = $next($request);

        Log::info('Access granted for route: ' . $currentRoute);

        return $response;
    }

    Log::warning('Unauthenticated user tried to access route: ' . $request->path());
    return redirect('/login');
}

    
    
}
