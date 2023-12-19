<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class AuthenticateStore extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->user() && $request->user()->type != 'store_user') {
            // Si el usuario está autenticado pero no es un usuario de la tienda, 
            // puedes redirigirlo a donde quieras.
            // Aquí simplemente estoy redirigiéndolos de vuelta a la página de inicio de sesión, 
            // pero podrías redirigirlos a otra página si prefieres.
            return route('login');
        }

        // Si el usuario no está autenticado en absoluto, redirígelo a la página de inicio de sesión.
        return $request->expectsJson() ? null : route('login');
    }
}
