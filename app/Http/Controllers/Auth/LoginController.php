<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Session;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Home\ValeFinancieroController;

class LoginController extends Controller
{
    private $userService, $coreService;
    private $request;
    private $valeFinancieroController;


    public function __construct(Request $request, UserService $userService, ValeFinancieroController $valeFinancieroController)
    {
        $this->request = $request;
        $this->userService = $userService;
        $this->valeFinancieroController = $valeFinancieroController;

    }

    public function index()
    {
        $data["title"] = "Inicio de sesión.";

        return view('auth.login')->with($data);
    }

    public function login(Request $request)
    {
        $response = array("status" => false, "message" => "Las credenciales enviadas no son correctas.");
    
        try
        {
            $messages = [
                'required' => 'El campo :attribute es requerido.',
                'usuario.max' => 'El campo usuario no debe superar los 100 caracteres.',
                'password.min' => 'El campo contraseña debe tener al menos un caracter.',
            ];
    
            $validator = Validator::make($request->all(), [
                "usuario"  => "required|max:100",
                "password" => "required|min:1"
            ], $messages);
    
            if ($validator->fails()) {
                $response["status"] = false;
                $response["message"] = $validator->errors()->first();
                return response()->json($response);  // Retorna inmediatamente la respuesta sin continuar con el resto del código
            }
            
    
            // Obtenemos una instancia del usuario y su tipo
            $userResult = $this->userService->getUser( $request->usuario, $request->password );
            $user = $userResult['user'];
            $userType = $userResult['type'];
            $userStatus = $userResult['status'];

            if($userStatus == '2'){
                $response["status"] = true;
                $response["message"] = "Need to renew password";
                $response["usuario"] = $request->usuario;
                $response["redirect"] = "renewPassword";  // Ruta a la que se redirigirá al cliente.
                if(!is_null($userResult['user']->UsuarioID)) {
                    $response["usuarioID"] = $userResult['user']->UsuarioID;
                } else {
                    $response["usuarioID"] = $userResult['user']->UsuarioTiendaID;
                }
                
                $response["tipo"] = $userType;
            } else if($userStatus == '1'){
                if (! empty($user)) {
                    if($user->exists) {
                        $token = $user->createToken('dpAIOWeb')->plainTextToken;
                        $this->userService->setSessionConnection(json_decode($user, true));
                        if($userType == 'store_user') {
                            Auth::guard('store')->login($user);
                            $request->session()->put('lastActivity', time());

                        } else {
                            Auth::guard('web')->login($user);
                            $request->session()->put('lastActivity', time());
                            $request->session()->put('usuarioLoginID', $user->id);

                        }
                        $response["status"] = true;
                        $response["message"] = "Correcto.";
                        $response["token"] = $token;  // Devuelve el token al cliente.
                    } else {
                        $response["message"] = "El usuario no existe. Por favor, verifique sus credenciales.";
                    }
                } else {
                    $response["message"] = $userResult['message'];
                }
            }else{
                $response["message"] = $userResult['message'];
            }	
          
        }
        catch (\Throwable $e)
        {
            Log::error($e); 
            $response["status"] = false;
            $response["message"] = $e->getMessage();
        }
        return response()->json($response);
    }
    

    public function logout()
    {
        echo "Cerrando sesión, espere un momento...";
        $response = array("status" => true, "message" => "Correcto.");
    
        try
        {
            Auth::logout();
    
            $this->request->session()->invalidate();
            $this->request->session()->regenerateToken();
        }
        catch (\Throwable $e)
        {
            //die( var_dump($e->getMessage()) );
            $response["status"] = false;
            $response["message"] = "Ha ocurrido un problema inesperado, favor de revisar.\n\nDetalles: " . $e->getMessage();
        }
    
        return redirect()->route('login');  // Modifica esta línea
    }
    
    public function authenticate(Request $request)
    {
        // Log::info('Authenticate request:', $request->all());

        $response = array("status" => 0, "message" => "Las credenciales enviadas no son correctas."); // Status inicializado en 0.
    
        try
        {
            $userResult = $this->userService->authenticateUser( $request->usuario, $request->password, $request->redirect, $request->module );
            // Log::info('Authenticate user result:', $userResult); // Agregando este log para ver el resultado de la autenticación
            $userStatus = $userResult['authenticated'];
            $user = $userResult['user'];
            // dd(session()->all());
            // $storesResponse = $this->getStores($user->Plazas); 
            // dd(session('USERDATA')['CodPlaza']);
            // $storesResponse = $this->valeFinancieroController->getStores(session('USERDATA')['CodPlaza']);;
            // // dd($storesResponse);
            // if (isset($storesResponse['error']) && $storesResponse['error']) {
            //     $response["status"] = 0;
            //     $response["message"] = $storesResponse['error'];
            //     return response()->json($response);
            // }else{
                if ($userStatus) {

                    Auth::guard('web')->login($user);
                    // dd(Auth::guard('web')->user());
                    $request->session()->put('lastActivity', time());
    
                    // session(['RoleID' => $user->RoleID]);  
                    $module = $request->input('module');
                    $idModulo = $request->input('idModulo');
                    // dd($module,$idModulo);
                    $request->session()->put('module', $module);
                    $request->session()->put('idModulo', $idModulo);
                    $request->session()->put('RoleID', $user->RoleID);
                    $request->session()->put('Usuario', $user->Usuario);
                    $request->session()->put('TipoUsuario', $user->TipoUsuario);
                    $request->session()->put('IDuser', $user->IDuser);
                    $request->session()->put('Nombre', $user->Nombre);
                    $request->session()->put('NoColaborador', $user->NoColaborador);
                    $request->session()->put('CodPlaza', $user->Plazas);
                    $request->session()->put('Tiendas', $user->TiendaID);
                    // $request->session()->put('CodPlaza', $user->CodPlaza);
                    $roleId = session('RoleID');
                    //impriem todos losd atos de la sesion
                    // dd(session()->all());
                    // dd($user->RoleID);
                    // dd($userResult,$user->Usuario);
                    $response["status"] = $user->status;  
                    $response["message"] = "Correcto.";
                    $response["redirect"] = $userResult['route']; 
                    $response["usuario"] = $userResult['user']->Usuario;
                    // dd($userResult['user']->Usuario);
                    $response["tipoUsuario"] = $user->TipoUsuario; 
                    $response["iduser"] = $user->IDuser; 
                    $response["RoleID"] = $user->RoleID;
                    $response["UsuarioCol"] = $user->Usuario;
        
                    // Auth::user()->update($user->toArray());
                    
                    // Auth::login(Auth::user());
                    
                } else {
                    $response["message"] = $userResult['msn']; 
                }
            // }
           
            
        }
        catch (\Throwable $e)
        {
            Log::error($e); 
            // Log::error('Authenticate error:', $e); // Agregando este log para ver el error

            $response["status"] = 0;
            $response["message"] = $e->getMessage();
        }
    
        return response()->json($response);
    }
}
