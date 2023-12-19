<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Passport\Token;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('Personal Access Token')->accessToken;

            return redirect()->route('main')->with('access_token', $token);
        } else {
            return redirect()->route('login')->withErrors(['email' => 'Email o contraseña invalidos']);
        }
    }
    public function logout(Request $request)
    {
        
        $accessToken = $request->user()->token();
    
    
        if ($accessToken) {
            $accessToken->revoke();
        }
    

        $refreshTokens = Token::where('user_id', $request->user()->id)->get();
        foreach ($refreshTokens as $refreshToken) {
            $refreshToken->revoke();
        }
    
        return redirect()->route('login');

    }
    


}
