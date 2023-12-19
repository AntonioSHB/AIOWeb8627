<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Mail\CorreoAltaUsuariosMailable;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Validator;
use App\Services\SessionService;
use Illuminate\Support\Facades\DB;

class GestionUsuariosController extends Controller
{    
    
    protected $sessionService;
    protected $request;

    public function __construct(Request $request, SessionService $sessionService)
    {
        $this->request = $request;
        $this->sessionService = $sessionService;

    }

    public function index()
    {
        //$randomPassword = Str::random(10); //Se genera una contraseña aleatoria de 10 caracteres        
        //$randomPassword = ''; 
        //var_dump($randomPassword);
        //$hashedPassword = hash('sha3-256', $randomPassword); //Se encripta contraseña
        //dd($hashedPassword);
        
        $title = 'Gestión de Usuarios';
        
        $CodPlaza = session("USERDATA")["Plazas"];
        $TiendaID = session("USERDATA")["TiendaID"];

        $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);
        
        try{
            DB::enableQueryLog();
            $catalogoRoles = $this->getCatalogoRoles();
            $catalogoUsuarios = Configuracion::getCatalogoUsuarios(null, null);
            $catalogoUsuariosTienda = Configuracion::getCatalogoUsuariosTienda(null, null);
            $catalogoPlazas = Configuracion::getCatalogoPlazas();
            $catalogoTiendas = Configuracion::getCatalogoTiendas(null);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion index().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion index()', $error);
            return false;
        }
                
        return view('home.aplicaciones.gestionUsuarios.index',
                    compact('title', 'catalogoRoles', 'catalogoUsuarios', 'catalogoUsuariosTienda', 'catalogoPlazas', 'catalogoTiendas', 'sessionLifetime')
                    );

    }

    public function getCatalogoRoles(){        
        try{
            DB::enableQueryLog();            
            return Configuracion::getCatalogoRoles();
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoRoles().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoRoles()', $error);
            return false;
        }
    }

    public function getCatalogoUsuarios(Request $request){
        $RoleID = $request->input('RoleID');
        $NoColaborador = $request->input('NoColaborador');
        try{
            DB::enableQueryLog();
            return Configuracion::getCatalogoUsuarios($RoleID, $NoColaborador);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoUsuarios().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoUsuarios()', $error);
            return false;
        }        
    }

    public function getCatalogoUsuariosTienda(Request $request){
        $CodPlaza = $request->input('CodPlaza');
        $TiendaID = $request->input('TiendaID');
        try{
            DB::enableQueryLog();
            return Configuracion::getCatalogoUsuariosTienda($CodPlaza, $TiendaID);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoUsuariosTienda().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoUsuariosTienda()', $error);
            return false;
        }        
    }

    public function buscarUsuario(Request $request){
        $NoColaborador = $request->input('inputValue');        
        $tipoUser = $request->input('TipoUser');
        
        $req = [
            'NoColaborador' => $NoColaborador,
            'Usuario' => null,
            'Nombre' => null,
            'TipoUser' => $tipoUser,
            'TipoRequest' => '1'
        ];

        $existe = $this->validaExistUsuario(new Request($req));        
        
        if ($existe) {            
            $objeto = [
                'existe' => $existe
            ];
            return response()->json($objeto);
        }else{            
            try{
                DB::enableQueryLog();
                $result = Configuracion::BuscarUsuario($NoColaborador);
                return response()->json($result);   
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion buscarUsuario().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion buscarUsuario()', $error);
                return false;
            }
        }       
        
    }

    public function getCatalogoPlazas(){
        
        try{
            DB::enableQueryLog();
            return Configuracion::getCatalogoPlazas();
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoPlazas().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoPlazas()', $error);
            return false;
        }
    }

    public function getCatalogoTiendas(Request $request){
        $CodPlaza = $request->input('CodPlaza');        
        try{
            DB::enableQueryLog();
            return Configuracion::getCatalogoTiendas($CodPlaza);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoTiendas().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoTiendas()', $error);
            return false;
        }
    }

    public function validaExistUsuario(Request $request){
        $NoColaborador = $request->input('NoColaborador');
        $Usuario = $request->input('Usuario');
        $Nombre = $request->input('Nombre');
        $tipoUser = $request->input('TipoUser');
        $TipoRequest = $request->input('TipoRequest');        
        try{
            DB::enableQueryLog();

            if($tipoUser == '1'){
                $existe = Configuracion::ExisteUsuario($NoColaborador, $Usuario, $Nombre);
                    
                if ($TipoRequest == '1'){
                    return $existe;
                }else if($TipoRequest == '2'){
                    $objeto = [
                        'existe' => $existe
                    ];
                    return response()->json($objeto);
                }
            }else if($tipoUser == '2'){
                $existe = Configuracion::ExisteUsuarioTienda($NoColaborador, $Usuario, $Nombre);
                    
                if ($TipoRequest == '1'){
                    return $existe;
                }else if($TipoRequest == '2'){
                    $objeto = [
                        'existe' => $existe
                    ];
                    return response()->json($objeto);
                }
            }
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion validaExistUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion validaExistUsuario()', $error);
            return false;
        }
    }

    public function validaDominioCorreo($Correo){
        
        try{
            DB::enableQueryLog();
            $Dominios = Configuracion::obtenerValorPorParametro('CorreoDominios');
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion validaDominioCorreo().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion validaDominioCorreo()', $error);
            return false;
        }
        
        $dominioExiste = false;
        $dominio = explode('@', $Correo)[1];        
        $array = explode(',', $Dominios);

        foreach($array as $elemento){
            if ($elemento == $dominio) {
                $dominioExiste = true;
            }
        }

        return $dominioExiste;
    }
       

    public function guardarUsuario(Request $request){
        
        $randomPassword = Str::random(10); //Se genera una contraseña aleatoria de 10 caracteres        
        $hashedPassword = hash('sha3-256', $randomPassword); //Se encripta contraseña
        
        $Usuario = $request->input('inputUsuario');
        $Nombre = $request->input('inputNombre');
        $NoColaborador = $request->input('inputNumColab');
        $RoleID = $request->input('comboModRoles');
        $Role = $request->input('Role');
        $Plazas = null;

        if(in_array($RoleID, ['4','5'])){
            $Plazas = $request->input('comboPlazaAlter');
        }else if(in_array($RoleID, ['6'])){
            $Plazas = $request->input('comboPlaza');
        }
        
        $TiendaID = $request->input('comboTienda');        
        $Correo = $request->input('inputCorreo');        
        $usuarioAlta = session("USERDATA")["Usuario"];
       
        $validatorResponse = [];
        
        if($RoleID == null || !in_array($RoleID, ['1', '2', '3', '4', '5', '6', '7'])){
            
            $messages = [
                'required' => 'El campo :attribute es requerido.',
                'comboModRoles.max' => 'El campo Roles no debe superar 1 caracter.',                
            ];
    
            $validator = Validator::make($request->all(), [
                'comboModRoles' => 'required|max:1',                
            ], $messages);
    
            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }            
        }else if (in_array($RoleID, ['6'])) {
            
            $dominioExiste = $this->validaDominioCorreo($Correo);
            if(!$dominioExiste){
                $objeto = [
                    'dominioExiste' => $dominioExiste
                ];
                return response()->json($objeto);
            }
            
            $messages = [
                'required' => 'El campo es requerido.',
                'comboModRoles.max' => 'El campo Roles no debe superar 1 caracter.',                
                'inputNumColab.max' => 'El campo debe tener máximo 8 digitos.',                
                'inputCorreo.email' => 'El campo debe ser un correo electrónico válido.'
            ];
    
            $validator = Validator::make($request->all(), [
                'inputUsuario' => 'required',
                'inputNombre' => 'required',
                'inputNumColab' => ['required', 'max:8'],
                'comboModRoles' => 'required',
                'comboPlaza' => 'required',
                'comboTienda' => 'required',                
                'inputCorreo' => ['required', 'email']
            ], $messages);
    
            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }            
        }else if(in_array($RoleID, ['4','5'])){            
            
            $dominioExiste = $this->validaDominioCorreo($Correo);
            if(!$dominioExiste){
                $objeto = [
                    'dominioExiste' => $dominioExiste
                ];
                return response()->json($objeto);
            }


            $messages = [
                'required' => 'El campo :attribute es requerido.',
                'comboModRoles.max' => 'El campo Roles no debe superar 1 caracter.',                
                'inputNumColab.max' => 'El campo debe tener máximo 8 digitos.',                
                'inputCorreo.email' => 'El campo debe ser un correo electrónico válido.'
            ];
    
            $validator = Validator::make($request->all(), [
                'inputUsuario' => 'required',
                'inputNombre' => 'required',
                'inputNumColab' => 'required|max:8',
                'comboModRoles' => 'required',
                'comboPlazaAlter' => 'required',                
                'inputCorreo' => 'required|email'
            ], $messages);
    
            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }
        }else if(in_array($RoleID, ['1', '2', '3', '7'])){
            
            $dominioExiste = $this->validaDominioCorreo($Correo);
            if(!$dominioExiste){
                $objeto = [
                    'dominioExiste' => $dominioExiste
                ];
                return response()->json($objeto);
            }
            

            $messages = [
                'required' => 'El campo :attribute es requerido.',
                'comboModRoles.max' => 'El campo Roles no debe superar 1 caracter.',                
                'inputNumColab.max' => 'El campo debe tener máximo 8 digitos.',                
                'inputCorreo.email' => 'El campo debe ser un correo electrónico válido.'
            ];
    
            $validator = Validator::make($request->all(), [
                'inputUsuario' => 'required',
                'inputNombre' => 'required',
                'inputNumColab' => 'required|max:8',
                'comboModRoles' => 'required',                
                'inputCorreo' => 'required|email'
            ], $messages);
    
            if ($validator->fails()) {
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }             
        }
            
        try{
            DB::enableQueryLog();
            $responseGuardar = Configuracion::GuardarUsuario($Usuario, $Nombre, $NoColaborador, $RoleID, $Plazas, $TiendaID, $hashedPassword, $usuarioAlta);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion guardarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion guardarUsuario()', $error);
            return false;
        }        
        
        if ($responseGuardar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseGuardar[0]->status,
                'msn' => $responseGuardar[0]->msn,
                'correo' => '0'
            ];
            
            return response()->json($responseStruct);

        }else if($responseGuardar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '1', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,
                'Nombre' => $Nombre,
                'Role' => $Role,
                'Usuario' => $Usuario,
                'Contraseña' => $randomPassword                
            ];

            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
                        
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseGuardar[0]->status,
                    'msn' => $responseGuardar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseGuardar[0]->status,
                    'msn' => $responseGuardar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }
        
    }

    public function guardarUsuarioTienda(Request $request){
                
        $randomPassword = Str::random(10); //Se genera una contraseña aleatoria de 10 caracteres        
        $hashedPassword = hash('sha3-256', $randomPassword); //Se encripta contraseña
        
        $Usuario = $request->input('inputUsuarioTienda');        
        $CodPlaza = $request->input('comboPlazaTienda');
        $TiendaID = intval($request->input('comboTiendaTienda'));                
        $Oficina = $request->input('inputOficinaTienda');
        $TiendaOVTA = $request->input('inputOVTATienda');
        $Correo = $request->input('inputCorreoTienda');        

        $validatorResponse = [];

        $usuarioAlta = session("USERDATA")["Usuario"];
        
        $dominioExiste = $this->validaDominioCorreo($Correo);
        if(!$dominioExiste){
            $objeto = [
                'dominioExiste' => $dominioExiste
            ];
            return response()->json($objeto);
        }

        $messages = [
            'required' => 'El campo es requerido.',            
            'inputCorreo.email' => 'El valor debe ser un correo electrónico válido.'
        ];

        $validator = Validator::make($request->all(), [
            'inputUsuarioTienda' => 'required',            
            'comboPlazaTienda' => 'required',                        
            'comboTiendaTienda' => 'required',           
            'inputOficinaTienda' => 'required',
            'inputOVTATienda' => 'required',                 
            'inputCorreoTienda' => ['required', 'email']
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $errors->keys()[$counter];                    
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;
            return response($response);
        }
            
        try{       
            DB::enableQueryLog();    
            $responseGuardar = Configuracion::GuardarUsuarioTienda($Usuario, $CodPlaza, $TiendaID, $Oficina, $TiendaOVTA, $hashedPassword, $usuarioAlta);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion guardarUsuarioTienda().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion guardarUsuarioTienda()', $error);
            return false;
        }        
        
        if ($responseGuardar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseGuardar[0]->status,
                'msn' => $responseGuardar[0]->msn,
                'correo' => '0'
            ];
            
            return response()->json($responseStruct);

        }else if($responseGuardar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '4', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,                
                'Usuario' => $Usuario,
                'Contraseña' => $randomPassword                
            ];

            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
            
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseGuardar[0]->status,
                    'msn' => $responseGuardar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseGuardar[0]->status,
                    'msn' => $responseGuardar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }
        
    }
    
    public function editarUsuario(Request $request){        
        $Usuario = $request->input('inputUsuarioEdit');
        $Nombre = $request->input('inputNombreEdit');
        $NoColaborador = $request->input('inputNumColabEdit');
        $RoleID = $request->input('comboModRolesEdit');
        $Role = $request->input('Role');
        $Plazas = null;
        
        if(in_array($RoleID, ['4','5'])){
            $Plazas = $request->input('comboPlazaAlterEdit');
        }else if(in_array($RoleID, ['6'])){
            $Plazas = $request->input('comboPlazaEdit');
        }

        $TiendaID = $request->input('comboTiendaEdit');        
        $Correo = $request->input('inputCorreoEdit');

        $usuarioMod = session("USERDATA")["Usuario"];

        $validatorResponse = [];
        
        if($RoleID == null || !in_array($RoleID, ['1', '2', '3', '4', '5', '6', '7'])){
            
            $messages = [
                'required' => 'El campo :attribute es requerido.',
                'comboModRoles.max' => 'El campo Roles no debe superar 1 caracter.',                
            ];
    
            $validator = Validator::make($request->all(), [
                'comboModRolesEdit' => 'required|max:1',                
            ], $messages);
    
            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }            
        }else if (in_array($RoleID, ['6'])) {
            
            $dominioExiste = $this->validaDominioCorreo($Correo);
            if(!$dominioExiste){
                $objeto = [
                    'dominioExiste' => $dominioExiste
                ];
                return response()->json($objeto);
            }
            
            $messages = [
                'required' => 'El campo es requerido.',
                'comboModRolesEdit.max' => 'El campo Roles no debe superar 1 caracter.',                
                'inputNumColabEdit.max' => 'El campo debe tener máximo 8 digitos.',                
                'inputCorreoEdit.email' => 'El campo debe ser un correo electrónico válido.'
            ];
    
            $validator = Validator::make($request->all(), [
                'inputUsuarioEdit' => 'required',
                'inputNombreEdit' => 'required',
                'inputNumColabEdit' => ['required', 'max:8'],
                'comboModRolesEdit' => 'required',
                'comboPlazaEdit' => 'required',
                'comboTiendaEdit' => 'required',                
                'inputCorreoEdit' => ['required', 'email']
            ], $messages);
    
            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }
        }else if(in_array($RoleID, ['4','5'])){
            
            $dominioExiste = $this->validaDominioCorreo($Correo);
            if(!$dominioExiste){
                $objeto = [
                    'dominioExiste' => $dominioExiste
                ];
                return response()->json($objeto);
            }

            $messages = [
                'required' => 'El campo es requerido.',
                'comboModRolesEdit.max' => 'El campo Roles no debe superar 1 caracter.',                
                'inputNumColabEdit.max' => 'El campo debe tener máximo 8 digitos.',                
                'inputCorreoEdit.email' => 'El campo debe ser un correo electrónico válido.'
            ];
    
            $validator = Validator::make($request->all(), [
                'inputUsuarioEdit' => 'required',
                'inputNombreEdit' => 'required',
                'inputNumColabEdit' => 'required|max:8',
                'comboModRolesEdit' => 'required|max:1',
                'comboPlazaAlterEdit' => 'required',                
                'inputCorreoEdit' => 'required|email'
            ], $messages);

            if ($validator->fails()) {
                
                $errors = $validator->errors();
                $counter = 0;

                foreach($errors->all() as $message){
                    $field = $errors->keys()[$counter];                    
                    $validatorResponse[$field] = $message;
                    $counter++;
                }
                
                $response["errors"] = $validatorResponse;
                return response($response);
            }

        }
        
        try{
            DB::enableQueryLog();    
            $responseEditar = Configuracion::EditarUsuario($NoColaborador, $Plazas, $TiendaID, $usuarioMod);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion editarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion editarUsuario()', $error);
            return false;
        }        
        
        
        if ($responseEditar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseEditar[0]->status,
                'msn' => $responseEditar[0]->msn,
                'correo' => '0'
            ];

            return response()->json($responseStruct);

        }else if($responseEditar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '2', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,
                'Nombre' => $Nombre,
                'Role' => $Role,
                'Usuario' => $Usuario,
                'Plazas' => $Plazas,
                'TiendaID' => $TiendaID
            ];
            
            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
            
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseEditar[0]->status,
                    'msn' => $responseEditar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseEditar[0]->status,
                    'msn' => $responseEditar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }
    }

    public function editarUsuarioTienda(Request $request){        
        
        $Usuario = $request->input('inputUsuarioTiendaEdit');        
        $CodPlaza = $request->input('comboPlazaTiendaEdit');
        $TiendaID = $request->input('comboTiendaTiendaEdit'); 
        $Oficina = $request->input('inputOficinaTiendaEdit'); 
        $TiendaOVTA = $request->input('inputOVTATiendaEdit'); 
        $Correo = $request->input('inputCorreoTiendaEdit');

        $usuarioMod = session("USERDATA")["Usuario"];
        // dd($Usuario, $CodPlaza, $TiendaID, $Oficina, $TiendaOVTA, $Correo, $usuarioMod);
        $validatorResponse = [];
        
        $dominioExiste = $this->validaDominioCorreo($Correo);
        // dd($dominioExiste);
        if(!$dominioExiste){
            $objeto = [
                'dominioExiste' => $dominioExiste
            ];
            return response()->json($objeto);
        }
        
        $messages = [
            'required' => 'El campo es requerido.',
            'inputOficinaTiendaEdit.max' => 'El campo Oficina no debe superar los 2 caracteres.',
            'inputOVTATiendaEdit.max' => 'El campo Tienda OVTA no debe superar los 5 caracteres.',                
            'inputCorreoTiendaEdit.email' => 'El campo debe ser un correo electrónico válido.'
        ];

        $validator = Validator::make($request->all(), [
            'inputUsuarioTiendaEdit' => 'required',
            'comboPlazaTiendaEdit' => 'required',
            'comboTiendaTiendaEdit' => 'required',
            'inputOficinaTiendaEdit' => 'required|max:2',
            'inputOVTATiendaEdit' => 'required|max:5',            
            'inputCorreoTiendaEdit' => 'required|email'
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $errors->keys()[$counter];                    
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;
            return response($response);
        }
        
        try{
            DB::enableQueryLog();

            $responseEditar = Configuracion::EditarUsuarioTienda($Usuario, $CodPlaza, $TiendaID, $usuarioMod, $Oficina, $TiendaOVTA);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion editarUsuarioTienda().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion editarUsuarioTienda()', $error);
            return false;
        }        
        
        
        if ($responseEditar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseEditar[0]->status,
                'msn' => $responseEditar[0]->msn,
                'correo' => '0'
            ];

            return response()->json($responseStruct);

        }else if($responseEditar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '5', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,
                'Usuario' => $Usuario,
                'CodPlaza' => $CodPlaza,
                'TiendaID' => $TiendaID,
                'Oficina' => $Oficina,
                'TiendaOVTA' => $TiendaOVTA
            ];
            
            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
            
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseEditar[0]->status,
                    'msn' => $responseEditar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseEditar[0]->status,
                    'msn' => $responseEditar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }
    }

    public function reiniciarUsuario(Request $request){
        $tipoUsuario = $request->input('tipoUsuarioReinicio');
        $UsuarioID = $request->input('UsuarioIDReinicio');
        $Nombre = $request->input('NombreReinicio');
        $Role = $request->input('RoleReinicio');
        $Usuario = $request->input('UsuarioReinicio');
        $Correo = $request->input('inputCorreoReinicio');

        $usuarioMod = session("USERDATA")["Usuario"];
        
        $randomPassword = Str::random(10); //Se genera una contraseña aleatoria de 10 caracteres        
        $Clave = hash('sha3-256', $randomPassword); //Se encripta contraseña

        $validatorResponse = [];

        $dominioExiste = $this->validaDominioCorreo($Correo);        
        if(!$dominioExiste){
            $objeto = [
                'dominioExiste' => $dominioExiste
            ];
            return response()->json($objeto);
        }

        $messages = [
            'required' => 'El campo es requerido.',            
            'tipoUsuarioReinicio.max' => 'El campo Usuario no debe superar 1 caracter.',
            'RoleReinicio.max' => 'El campo Role no debe superar 1 caracter.',
            'inputCorreoReinicio.email' => 'El campo debe ser un correo electrónico válido.'
        ];

        $validator = Validator::make($request->all(), [
            'tipoUsuarioReinicio' => 'required|max:1',
            'UsuarioIDReinicio' => 'required',
            'NombreReinicio' => 'required',
            'RoleReinicio' => 'required',
            'UsuarioReinicio' => 'required',            
            'inputCorreoReinicio' => 'required|email'
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $errors->keys()[$counter];                    
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;
            return response($response);
        }

        try{
            // DB::enableQueryLog();
            $responseReiniciar = Configuracion::ReiniciarUsuario($tipoUsuario, $UsuarioID, $Clave, $usuarioMod);
        }catch(\Throwable $t){
            // $queries = DB::getQueryLog();
            // $lastQuery = end($queries); // Obtener la última consulta ejecutada
            // $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            // $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion reiniciarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
                // 'requestLog' => $requestLog, // Agregar el SQL al log
                // 'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion reiniciarUsuario()', $error);
            return false;
        }

        if ($responseReiniciar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseReiniciar[0]->status->tostring(),
                'msn' => $responseReiniciar[0]->msn,
                'correo' => '0'
            ];
            
            return response()->json($responseStruct);

        }else if($responseReiniciar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '3', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,
                'Nombre' => $Nombre,
                'Role' => $Role,
                'Usuario' => $Usuario,
                'Contraseña' => $randomPassword
            ];

            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
            
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseReiniciar[0]->status,
                    'msn' => $responseReiniciar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseReiniciar[0]->status,
                    'msn' => $responseReiniciar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }

    }

    public function reiniciarUsuarioTienda(Request $request){
        $tipoUsuario = $request->input('tipoUsuarioReinicioTienda');
        $UsuarioID = $request->input('UsuarioIDReinicioTienda');        
        $Usuario = $request->input('UsuarioReinicioTienda');
        $Correo = $request->input('inputCorreoReinicioTienda');

        $usuarioMod = session("USERDATA")["Usuario"];
        
        $randomPassword = Str::random(10); //Se genera una contraseña aleatoria de 10 caracteres        
        $Clave = hash('sha3-256', $randomPassword); //Se encripta contraseña

        $dominioExiste = $this->validaDominioCorreo($Correo);        
        if(!$dominioExiste){
            $objeto = [
                'dominioExiste' => $dominioExiste
            ];
            return response()->json($objeto);
        }

        $messages = [
            'required' => 'El campo es requerido.',            
            'tipoUsuarioReinicioTienda.max' => 'El campo Usuario no debe superar 1 caracter.',            
            'inputCorreoReinicio.email' => 'El campo debe ser un correo electrónico válido.'
        ];

        $validator = Validator::make($request->all(), [
            'tipoUsuarioReinicioTienda' => 'required|max:1',
            'UsuarioIDReinicioTienda' => 'required',                        
            'UsuarioReinicioTienda' => 'required',            
            'inputCorreoReinicioTienda' => 'required|email'
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $errors->keys()[$counter];                    
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;
            return response($response);
        }

        try{         
            DB::enableQueryLog();   
            $responseReiniciar = Configuracion::ReiniciarUsuario($tipoUsuario, $UsuarioID, $Clave, $usuarioMod);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion reiniciarUsuarioTienda().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion reiniciarUsuarioTienda()', $error);
            return false;
        }

        if ($responseReiniciar[0]->status == '0') {
            $responseStruct = [
                'status' => $responseReiniciar[0]->status,
                'msn' => $responseReiniciar[0]->msn,
                'correo' => '0'
            ];
            
            return response()->json($responseStruct);

        }else if($responseReiniciar[0]->status == '1'){
            
            $dataCorreo = [
                'type' => '6', //1- Alta de Usuario, 2- Edicion de Usuario, 3 Reinicio Usuario, 4 Alta de UsuarioTienda, 5 Edicion Usuario Tienda, 6 Reinicio Usuario Tienda
                'Correo' => $Correo,                
                'Usuario' => $Usuario,
                'Contraseña' => $randomPassword
            ];

            $resEnvCorreo = $this->enviarCorreo($dataCorreo);
            
            if ($resEnvCorreo) {                
                $responseStruct = [
                    'status' => $responseReiniciar[0]->status,
                    'msn' => $responseReiniciar[0]->msn,
                    'correo' => '1'
                ];                
                return response()->json($responseStruct);
            }else{                
                $responseStruct = [
                    'status' => $responseReiniciar[0]->status,
                    'msn' => $responseReiniciar[0]->msn,
                    'correo' => '0'
                ];                
                return response()->json($responseStruct);
            }
        }

    }

    public function deshabilitarUsuario(Request $request){
        $tipoUsuario = $request->input('tipoUsuario');
        $UsuarioID = $request->input('UsuarioID');

        $usuarioMod = session("USERDATA")["Usuario"];

        try{            
            DB::enableQueryLog();   

            $responseDeshabilitar = Configuracion::DeshabilitarUsuario($tipoUsuario, $UsuarioID, $usuarioMod);
            
            if ($responseDeshabilitar[0]->status == '0') {
                $responseStruct = [
                    'status' => false,
                    'msn' => $responseDeshabilitar[0]->msn,                    
                ];
                
                return response()->json($responseStruct);    
            }else if ($responseDeshabilitar[0]->status == '1'){
                $responseStruct = [
                    'status' => true,
                    'msn' => $responseDeshabilitar[0]->msn,                    
                ];
                
                return response()->json($responseStruct);
            }            
            return response()->json($responseDeshabilitar);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion deshabilitarUsuario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion deshabilitarUsuario()', $error);
            return false;
        }        
    }

    public function deshabilitarUsuarioTienda(Request $request){
        $tipoUsuario = $request->input('tipoUsuario');
        $UsuarioID = $request->input('UsuarioID');

        $usuarioMod = session("USERDATA")["Usuario"];

        try{            
            DB::enableQueryLog();   

            $responseDeshabilitar = Configuracion::DeshabilitarUsuario($tipoUsuario, $UsuarioID, $usuarioMod);
            
            if ($responseDeshabilitar[0]->status == '0') {
                $responseStruct = [
                    'status' => false,
                    'msn' => $responseDeshabilitar[0]->msn,                    
                ];
                
                return response()->json($responseStruct);    
            }else if ($responseDeshabilitar[0]->status == '1'){
                $responseStruct = [
                    'status' => true,
                    'msn' => $responseDeshabilitar[0]->msn,                    
                ];
                
                return response()->json($responseStruct);
            }            
            return response()->json($responseDeshabilitar);
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion deshabilitarUsuarioTienda().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion deshabilitarUsuarioTienda()', $error);
            return false;
        }        
    }

    public function enviarCorreo($dataCorreo){        
        $asunto = '';
        $datos = [];

        if ($dataCorreo['type'] == '1') {
            try{
                DB::enableQueryLog();   
                $urlPortal = Configuracion::obtenerValorPorParametro('UrlAioWeb');            
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion enviarCorreo().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion enviarCorreo()', $error);
                return false;
            }            
            $asunto = 'Correo de confirmación de alta de usuario';
            $datos = [$dataCorreo['type'], $dataCorreo['Nombre'], $dataCorreo['Role'], $dataCorreo['Usuario'], $dataCorreo['Contraseña'], $urlPortal];
        }elseif ($dataCorreo['type'] == '2') {
            $asunto = 'Correo de confirmación de edición de usuario';            
            $datos = [$dataCorreo['type'], $dataCorreo['Nombre'], $dataCorreo['Role'], $dataCorreo['Usuario'], $dataCorreo['Plazas'][0], $dataCorreo['TiendaID']];
        }elseif ($dataCorreo['type'] == '3') {
            try{
                DB::enableQueryLog();   
                $urlPortal = Configuracion::obtenerValorPorParametro('UrlAioWeb');            
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion enviarCorreo().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion enviarCorreo()', $error);
                return false;
            }
            $asunto = 'Correo de confirmación de reinicio de contraseña de usuario';            
            $datos = [$dataCorreo['type'], $dataCorreo['Nombre'], $dataCorreo['Role'], $dataCorreo['Usuario'], $dataCorreo['Contraseña'], $urlPortal];
        }else if ($dataCorreo['type'] == '4') {
            try{
                DB::enableQueryLog();   
                $urlPortal = Configuracion::obtenerValorPorParametro('UrlAioWeb');            
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion enviarCorreo().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion enviarCorreo()', $error);
                return false;
            }            
            //$asunto = 'Correo de confirmación de Alta de Usuario Tienda';
            $asunto = 'Correo de confirmación de usuario';
            $datos = [$dataCorreo['type'], $dataCorreo['Usuario'], $dataCorreo['Contraseña'], $urlPortal];
        }else if ($dataCorreo['type'] == '5') {                        
            //$asunto = 'Correo de confirmación de edición de usuario Tienda';
            $asunto = 'Correo de confirmación de edición de usuario';
            $datos = [$dataCorreo['type'], $dataCorreo['Usuario'], $dataCorreo['CodPlaza'], $dataCorreo['TiendaID'], $dataCorreo['Oficina'], $dataCorreo['TiendaOVTA']];
        }else if ($dataCorreo['type'] == '6') {
            try{
                DB::enableQueryLog();   
                $urlPortal = Configuracion::obtenerValorPorParametro('UrlAioWeb');            
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion enviarCorreo().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion enviarCorreo()', $error);
                return false;
            }
            $asunto = 'Correo de confirmación de reinicio de usuario';
            //$asunto = 'Correo de confirmación de edición de usuario Tienda';
            $datos = [$dataCorreo['type'], $dataCorreo['Usuario'], $dataCorreo['Contraseña'], $urlPortal];
        }
                
        $correo = new CorreoAltaUsuariosMailable($datos, $asunto);        
        $destinatario = $dataCorreo['Correo'];        

        try{
            $result = Mail::to($destinatario)->send($correo);      
            // dd($result);      
            return true;
        }catch(\Throwable $t){
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion enviarCorreo() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => [$datos, $correo],
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion enviarCorreo() al consumir el servicio', $error);
            return false;
        }
    }

    public function exportTable(Request $request)
    {        
        try{
            $data = $request->input('arrayData');
            $tablaIn = $request->input('tablaIn');
            $typeExport = $request->input('typeExport');
            
            $dataSession = session("USERDATA");                
            $usuarioActivo = $dataSession['Nombre'];
                    
            // Crear un nuevo objeto Spreadsheet
            $spreadsheet = new Spreadsheet();
            
            // Obtener la hoja de trabajo activa
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setTitle("TablaUsuarios");

            $sheet->mergeCells('A1:F1');
            $sheet->mergeCells('A2:F2');
            $sheet->mergeCells('A3:F3');
            $sheet->mergeCells('A4:F4');

            $styleArrayHead = [
                'font' => [
                    'bold' => true,
                    'size' => 14
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]            
            ];

            $sheet->getCell('A1')->getStyle()->applyFromArray($styleArrayHead);
            $sheet->getCell('A2')->getStyle()->applyFromArray($styleArrayHead);
            $sheet->getCell('A3')->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getCell('A4')->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            $sheet->setCellValue('A1', 'DP AIO');
            $sheet->setCellValue('A2', 'Gestión de Usuarios');
            $sheet->setCellValue('A3', 'Elaborado por el usuario: '.$usuarioActivo);//nombre del colaborador con rol de soporte que exporto la tabla
            $sheet->setCellValue('A4', 'Fecha: '.date("d-m-Y"));
            
            $spreadsheet->getActiveSheet()->getCell('A5')->setValue('');
                    
            if ($tablaIn == '1') {
                $sheet->setCellValue('A6', 'Usuario');
                $sheet->setCellValue('B6', 'Nombre');
                $sheet->setCellValue('C6', 'Colaborador');
                $sheet->setCellValue('D6', 'Rol');
                $sheet->setCellValue('E6', 'Plaza');
                $sheet->setCellValue('F6', 'Tienda');                
            }else if($tablaIn == '2'){
                $sheet->setCellValue('A6', 'Usuario');
                $sheet->setCellValue('B6', 'Tienda');
                $sheet->setCellValue('C6', 'Plaza');
                $sheet->setCellValue('D6', 'Oficina');
                $sheet->setCellValue('E6', 'OVTA');            
            }
            

            $styleArrayTHead = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]            
            ];
            
            if ($tablaIn == '1') {
                $sheet->getCell('A6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('B6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('C6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('D6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('E6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('F6')->getStyle()->applyFromArray($styleArrayTHead);                
            }else if ($tablaIn == '2') {
                $sheet->getCell('A6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('B6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('C6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('D6')->getStyle()->applyFromArray($styleArrayTHead);
                $sheet->getCell('E6')->getStyle()->applyFromArray($styleArrayTHead);            
            }        
            
            $row = 7;        
            foreach ($data as $item) {                       
                if ($tablaIn == '1') {
                    $sheet->setCellValue('A' . $row, $item[0]);
                    $sheet->setCellValue('B' . $row, $item[1]);
                    $sheet->setCellValue('C' . $row, $item[2]);
                    $sheet->setCellValue('D' . $row, $item[3]);
                    $sheet->setCellValue('E' . $row, $item[4]);
                    $sheet->setCellValue('F' . $row, $item[5]);                    
                }else if ($tablaIn == '2') {
                    $sheet->setCellValue('A' . $row, $item[0]);
                    $sheet->setCellValue('B' . $row, $item[1]);
                    $sheet->setCellValue('C' . $row, $item[2]);
                    $sheet->setCellValue('D' . $row, $item[3]);
                    $sheet->setCellValue('E' . $row, $item[4]);                
                }
                
                $row++;
            }
            
            // Agregar una imagen al archivo Excel
            $imagePath = public_path('assets/logoCompleto.png'); // Reemplaza 'ruta/imagen.jpg' con la ruta de tu imagen
            $drawing = new Drawing();
            $drawing->setName('LogoDPortenis');
            $drawing->setDescription('Logotipo DPortenis');        
            $drawing->setPath($imagePath);
            $drawing->setCoordinates('A1'); // Indica la celda en la que se insertará la imagen
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(10);
            $drawing->setWidthAndHeight(200, 200); // Establece el ancho y alto de la imagen
            $drawing->setWorksheet($sheet);
                    
            if ($tablaIn == '1') {
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(20);                
            }else if($tablaIn == '2'){
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(20);            
            }
            
            if ($tablaIn == '1') {
                if ($typeExport == 1) {
                    // Crear un objeto Writer para guardar el archivo Excel
                    $writer = new Xlsx($spreadsheet);                
                    // Guardar el archivo Excel en la ubicación deseada            
                    $excelPath = public_path('Usuarios-'.date("d-m-Y").'.xlsx'); // ubicación y nombre de archivo deseados            
                    $writer->save($excelPath);            
                    
                    return response()->download($excelPath,'Usuarios-'.date("d-m-Y").'.xlsx', [
                        'Content-Type' => 'application/vndopenxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend();
                }elseif ($typeExport == 2){ 
                    
                    $contenidoHtml = '<table>';

                    $contenidoHtml .= '<thead>';
                    $contenidoHtml .= '<tr>';
                        $contenidoHtml .= '<th>Usuario</th>';
                        $contenidoHtml .= '<th>Nombre</th>';
                        $contenidoHtml .= '<th>Colaborador</th>';
                        $contenidoHtml .= '<th>Rol</th>';
                        $contenidoHtml .= '<th>Plaza</th>';
                        $contenidoHtml .= '<th>Tienda</th>';                        
                    $contenidoHtml .= '</tr>';
                    $contenidoHtml .= '</thead>';

                    $contenidoHtml .= '<tbody>';                
                    foreach ($data as $fila) {
                        $contenidoHtml .= '<tr>';                    
                        $contenidoHtml .= '<td>' . $fila[0]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[1]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[2]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[3]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[4]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[5]. '</td>';                        
                        $contenidoHtml .= '</tr>';
                    }                
                    $contenidoHtml .= '</tbody>';
                    $contenidoHtml .= '</table>';
                    
                    // variables para incluir datos en la plantilla
                    $logoDP = '<img class="float-image" src='.public_path('assets/logoCompletoExp.jpg').' alt="logoDP">';                
                    $fecha = date('d-m-Y');
                    $usuarioSopLog = $dataSession['Nombre'];


                    // Plantilla HTML formateada
                    $plantillaHtml = <<<HTML
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <meta charset="UTF-8">
                        <style>                       

                            body {                        
                            font-family: Arial, sans-serif;                        
                            margin-top: 20px;
                            margin-left: 40px;
                            margin-right: 40px;
                            margin-bottom: 20px;                        
                            }

                            .header {
                            position: relative;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 20px;                        
                            }

                            .content {
                            margin-top: 0px;
                            }

                            .page-break {
                            page-break-after: always;
                            }

                            .footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            height: 40px;
                            text-align: center;
                            background-color: #f2f2f2;
                            line-height: 40px;
                            }

                            .float-image {
                                position: absolute;
                                top: 15px;
                                left: 10px;
                                width: 200;
                                z-index: 9999;
                            }

                            .t1 {
                                margin-top: 0px;
                                text-align: center;
                                font-size: 16px;
                            }

                            .t2 {                            
                                text-align: center;
                                font-size: 14px;
                            }

                            .t3 {
                                text-align: right;
                                font-size: 12px;
                            }

                            table {
                                width: 100%;
                                border-collapse: collapse;
                                border: 1px solid #BCC6DF;
                                font-size: 13px;
                            }
                            
                            th {
                                text-align: center;
                                weight: bold;
                                background: #D0DCF9;
                                border: 1px solid #BCC6DF;
                            }

                            td {
                                text-align: left;
                                border: 1px solid #BCC6DF;
                            }

                        </style>
                        </head>
                        <body>
                        <div class="header">                        
                            <div class="row">
                                <div class="float-image">
                                    $logoDP
                                </div>
                                <div class="col">
                                    <div class="t1"><strong>DP AIO</strong></div>
                                    <div class="t2"><strong>Gestión de Usuarios</strong></div>
                                    <br/>
                                </div>
                            </div>                        
                            <div class="t3"><strong>Elaborado por el usuario: </strong>$usuarioSopLog</div>
                            <div class="t3"><strong>Fecha: </strong>$fecha</div>                        
                            <br/>
                        </div>
                        <div class="content">
                            $contenidoHtml
                        </div>
                        <div class="footer">
                            DP Tabla de Usuarios
                        </div>
                        </body>
                        </html>
                        HTML;
                    
                    $mpdf = new Mpdf();

                    // Agregar el contenido HTML a mPDF
                    $mpdf->WriteHTML($plantillaHtml);

                    // Guardar el archivo Pdf en la ubicación deseada
                    $pdfPath = public_path('Usuarios-'.date("d-m-Y").'.pdf'); // ubicación y nombre de archivo deseados                                            
                    $mpdf->Output($pdfPath, 'F');
                    
                    return response()->download($pdfPath,'Usuarios-'.date("d-m-Y").'.pdf', [
                        'Content-Type' => 'application/pdf',
                    ])->deleteFileAfterSend();
                }
            }else if($tablaIn == '2') {
                if ($typeExport == 1) {
                    // Crear un objeto Writer para guardar el archivo Excel
                    $writer = new Xlsx($spreadsheet);
                    
                    // Guardar el archivo Excel en la ubicación deseada            
                    $excelPath = public_path('UsuariosTienda-'.date("d-m-Y").'.xlsx'); // ubicación y nombre de archivo deseados            
                    $writer->save($excelPath);            
                    
                    return response()->download($excelPath,'UsuariosTienda-'.date("d-m-Y").'.xlsx', [
                        'Content-Type' => 'application/vndopenxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend();
                }elseif ($typeExport == 2){
                    $contenidoHtml = '<table>';

                    $contenidoHtml .= '<thead>';
                    $contenidoHtml .= '<tr>';
                        $contenidoHtml .= '<th>Usuario</th>';
                        $contenidoHtml .= '<th>Nombre</th>';
                        $contenidoHtml .= '<th>Colaborador</th>';
                        $contenidoHtml .= '<th>Rol</th>';
                        $contenidoHtml .= '<th>Plaza</th>';
                        $contenidoHtml .= '<th>Tienda</th>';                    
                    $contenidoHtml .= '</tr>';
                    $contenidoHtml .= '</thead>';

                    $contenidoHtml .= '<tbody>';                
                    foreach ($data as $fila) {
                        $contenidoHtml .= '<tr>';                    
                        $contenidoHtml .= '<td>' . $fila[0]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[1]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[2]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[3]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[4]. '</td>';
                        $contenidoHtml .= '<td>' . $fila[5]. '</td>';                    
                        $contenidoHtml .= '</tr>';
                    }                
                    $contenidoHtml .= '</tbody>';
                    $contenidoHtml .= '</table>';
                    
                    // variables para incluir datos en la plantilla
                    $logoDP = '<img class="float-image" src='.public_path('assets/logoCompletoExp.jpg').' alt="logoDP">';                
                    $fecha = date('d-m-Y');
                    $usuarioSopLog = $dataSession['Nombre'];


                    // Plantilla HTML formateada
                    $plantillaHtml = <<<HTML
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <meta charset="UTF-8">
                        <style>                       

                            body {                        
                            font-family: Arial, sans-serif;                        
                            margin-top: 20px;
                            margin-left: 40px;
                            margin-right: 40px;
                            margin-bottom: 20px;                        
                            }

                            .header {
                            position: relative;
                            top: 0;
                            left: 0;
                            right: 0;
                            height: 20px;                        
                            }

                            .content {
                            margin-top: 0px;
                            }

                            .page-break {
                            page-break-after: always;
                            }

                            .footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            height: 40px;
                            text-align: center;
                            background-color: #f2f2f2;
                            line-height: 40px;
                            }

                            .float-image {
                                position: absolute;
                                top: 15px;
                                left: 10px;
                                width: 200;
                                z-index: 9999;
                            }

                            .t1 {
                                margin-top: 0px;
                                text-align: center;
                                font-size: 16px;
                            }

                            .t2 {                            
                                text-align: center;
                                font-size: 14px;
                            }

                            .t3 {
                                text-align: right;
                                font-size: 12px;
                            }

                            table {
                                width: 100%;
                                border-collapse: collapse;
                                border: 1px solid #BCC6DF;
                                font-size: 13px;
                            }
                            
                            th {
                                text-align: center;
                                weight: bold;
                                background: #D0DCF9;
                                border: 1px solid #BCC6DF;
                            }

                            td {
                                text-align: left;
                                border: 1px solid #BCC6DF;
                            }

                        </style>
                        </head>
                        <body>
                        <div class="header">                        
                            <div class="row">
                                <div class="float-image">
                                    $logoDP
                                </div>
                                <div class="col">
                                    <div class="t1"><strong>DP AIO</strong></div>
                                    <div class="t2"><strong>Gestión de Usuarios Tienda</strong></div>
                                    <br/>
                                </div>
                            </div>                        
                            <div class="t3"><strong>Elaborado por el usuario: </strong>$usuarioSopLog</div>
                            <div class="t3"><strong>Fecha: </strong>$fecha</div>                        
                            <br/>
                        </div>
                        <div class="content">
                            $contenidoHtml
                        </div>
                        <div class="footer">
                            DP Tabla de Usuarios Tienda
                        </div>
                        </body>
                        </html>
                        HTML;
                    
                    $mpdf = new Mpdf();

                    // Agregar el contenido HTML a mPDF
                    $mpdf->WriteHTML($plantillaHtml);

                    // Guardar el archivo Pdf en la ubicación deseada
                    $pdfPath = public_path('Usuarios-'.date("d-m-Y").'.pdf'); // ubicación y nombre de archivo deseados                                            
                    $mpdf->Output($pdfPath, 'F');
                    
                    return response()->download($pdfPath,'Usuarios-'.date("d-m-Y").'.pdf', [
                        'Content-Type' => 'application/pdf',
                    ])->deleteFileAfterSend();
                }
            }
        }catch(\Throwable $t){
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion canHaveLoan() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $data,
                'responseLog' => isset($response) ? $response : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion canHaveLoan() al consumir el servicio', $error);
            return false;
        }
        
    }

}
