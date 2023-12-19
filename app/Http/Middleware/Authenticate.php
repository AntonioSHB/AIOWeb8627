<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserService;

class Authenticate
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(Request $request, Closure $next)
    {
        $username = $request->input('usuario');
        $password = $request->input('password');
        $redirect = $request->input('redirect');
        $module = $request->input('module');

        $userResult = $this->userService->authenticateUser($username, $password, $redirect, $module);

        if ($userResult['authenticated']) {
            $user = $userResult['user'];
            Auth::login($user);
            $request->session()->put('RoleID', $user->RoleID);
        } else {
            // Manejo del caso en el que la autenticación no tenga éxito
            // Esto podría involucrar redirigir al usuario a la página de inicio de sesión con un mensaje de error
            return redirect('/login')->with('error', $userResult['msn']);
        }

        return $next($request);
    }
}
