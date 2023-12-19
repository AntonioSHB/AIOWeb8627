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
        return $next($request);

    }

    return redirect('/login');
}

    
    
}
