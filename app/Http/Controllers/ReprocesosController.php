<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Configuracion;
use App\Models\SaveLoan;
use Illuminate\Support\Facades\Log;
use App\Services\SessionService;
use App\Services\EncryptService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class ReprocesosController extends Controller
{
    protected $sessionService;
    protected $request;
    protected $EncryptService;

    public function __construct(Request $request, SessionService $sessionService, EncryptService $encryptService)
    {
        $this->request = $request;
        $this->sessionService = $sessionService;
        $this->EncryptService = $encryptService;
    }

    public function index()
    {        
        $title = 'Reprocesos';
        
        $prestamosFallidos = [];
        
        $CodPlaza = session("USERDATA")["CodPlaza"];
        $TiendaID = session("USERDATA")["TiendaID"];
        
        $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);

        try {
            DB::enableQueryLog();
            $consultaPrestamosFallidos = Configuracion::BuscaPrestamosFallidos($CodPlaza, $TiendaID, null);          
            // dd($consultaPrestamosFallidos);  
        }catch (\Throwable $t){            
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
        }        
            // dd($consultaPrestamosFallidos);
            if (Count($consultaPrestamosFallidos) == 0 || 
    (Count($consultaPrestamosFallidos) == 1 && isset($consultaPrestamosFallidos['error']))) {
            
            $prestamosFallidos = '0';

            try{
                DB::enableQueryLog();
                $vcBines = Configuracion::getBinesBancarios();
            }catch (\Throwable $t){
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
            }
        }else{     
            // dd("entro");       
            if (!isset($consultaPrestamosFallidos) || key(reset($consultaPrestamosFallidos)) == 'status'){
                $prestamosFallidos = '1';

                try{
                    DB::enableQueryLog();
                    $vcBines = Configuracion::getBinesBancarios();
                }catch (\Throwable $t){
                    $vcBines = '0';
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
                }
            }else{
                // dd("entro");       

                try{                    
                    $prestamosFallidos = $this->presToArray($consultaPrestamosFallidos);
                    // dd($prestamosFallidos);
                }catch (\Throwable $t){                
                    $prestamosFallidos = '2';
                    $error = [
                        'status' => '0',
                        'fecha' => date('Y-m-d H:i:s'),
                        'descripcion' => 'Error en la funcion index() al consumir el servicio.',
                        'codigoError' => $t->getCode(),
                        'msnError' => $t->getMessage(),
                        'linea' => $t->getLine(),
                        'archivo' => $t->getFile(),
                        'requestLog' => $consultaPrestamosFallidos,
                        'responseLog' => isset($prestamosFallidos) ? $prestamosFallidos : 'No response', // Verificar si $response existe
                    ];
                    Log::error('Error en la funcion index() al consumir el servicio', $error);
                    return false;
                }                        
    
                try{
                    DB::enableQueryLog();
                    $vcBines = Configuracion::getBinesBancarios();
                }catch (\Throwable $t){
                    $vcBines = '0';
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
                }            
            }
        }
        
        return view('home.aplicaciones.reprocesos.index',
                    compact('title', 'prestamosFallidos', 'CodPlaza', 'TiendaID', 'vcBines', 'sessionLifetime')
                    );        
    }

    public function actualizarTabla(){
        
        $CodPlaza = session("USERDATA")["CodPlaza"];
        $TiendaID = session("USERDATA")["TiendaID"];
        
        try{
            DB::enableQueryLog();
            $consultaPrestamosFallidos = Configuracion::BuscaPrestamosFallidos($CodPlaza, $TiendaID, null);
        }catch (\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion actualizarTabla().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion actualizarTabla()', $error);
        }
                
        if (key(reset($consultaPrestamosFallidos)) == 'status'){
            
            $prestamosFallidos = '0';
            return response()->json($prestamosFallidos);
            
        }else{
            
            $prestamosFallidos = $this->presToArray($consultaPrestamosFallidos);                        
            return response()->json($prestamosFallidos);            
        }        
    }    

    //Función para encryptar o desencryptar la tarjeta
    public function presToArray($consultaPrestamosFallidos, $maskIndic = 1){
        // dd($consultaPrestamosFallidos);
        
        foreach ($consultaPrestamosFallidos as &$value) {            
            $value->TarjetaClabe = $this->descifrarTarjeta(1, $value->Fecha, base64_decode($value->TarjetaClabe));
            // var_dump($value->TarjetaClabe);
            if ($value->TarjetaClabe == false) {
                return '2';
            }
            
            if ($maskIndic == 1) { //Valor 1 para enmascarar la tarjeta y 0 para no enmascararla
                $value->TarjetaClabe = $this->maskTarjeta($value->TarjetaClabe);
            }            
        }
        return $consultaPrestamosFallidos;
    }

    //Función para emnascarar caracteres de la tarjeta
    public function maskTarjeta($TarjetaClabe){
        if (strlen($TarjetaClabe) == 16) {                    
            $maskedValue = substr($TarjetaClabe, 0, 6) . '******' . substr($TarjetaClabe, -4);            
            return $maskedValue;
        }else if(strlen($TarjetaClabe) == 18 || strlen($TarjetaClabe) == 17){
            $maskedValue = substr($TarjetaClabe, 0, 6) . '********' . substr($TarjetaClabe, -4);
            return $maskedValue;
        }else{
            return false;
        }
    }

    //Función para descifrar la tarjeta
    public function descifrarTarjeta($Indicador, $Fecha, $TarjetaClabe){
        
        try{
            if ($Indicador == 0) {
                // dd($TarjetaClabe, $Fecha);
                if ($TarjetaClabe != null && $Fecha != null){                
                    $encryptName = $this->EncryptService->getEncrypt($Fecha);                
                    $resultEncrypt = $this->EncryptService->Encrypt($Indicador, $encryptName[0]->Nombre, $TarjetaClabe);
                    
                    if ($resultEncrypt){                    
                        return $resultEncrypt['Resultado'][0]['datos'][0]['data'];
                    }else{
                        return false;
                    }
                }
                else{
                    return false;
                }
            }else if($Indicador == 1){
                // dd("entro");
                // dd($TarjetaClabe, $Fecha);
                if ($TarjetaClabe != null && $Fecha != null){                
                    $encryptName = $this->EncryptService->getEncrypt($Fecha);
                                        
                    $resultDecrypt = $this->EncryptService->Encrypt($Indicador, $encryptName[0]->Nombre, $TarjetaClabe);

                    if ($resultDecrypt){                    
                        return $resultDecrypt['Resultado'][0]['datos'][0]['data'];
                    }else{
                        return false;
                    }
                }
                else{
                    return false;
                }
            }
        }catch(\Throwable $t){            
            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion descifrarTarjeta() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $encryptName,
                'responseLog' => isset($resultDecrypt) ? $resultDecrypt : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion descifrarTarjeta() al consumir el servicio', $error);
            
            return false;
        }        
    }    

    public function getCatalogoServicios(){        
        $CodPlaza = "MZT"; // Se obtiene del valor de ConfigAIO.Plaza
        try{
            DB::enableQueryLog();
            $catalogoServicios = Configuracion::getCatalogoServicios($CodPlaza);
            return response()->json($catalogoServicios);

        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoServicios().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoServicios()', $error);
            
            return response()->json($error);
        }        
    }

    public function getCatalogoBancos(){  
        try{
            DB::enableQueryLog();
            $catalogoBancos = Configuracion::getCatalogoBancos();
            return response()->json($catalogoBancos);

        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoBancos().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoBancos()', $error);
            
            return response()->json($error);
        }        
    }

    public function ValidaPrestamoxTarjetaDias(Request $request){        
        if(isset($request)){
            $tarjeta = $request->input('Tarjeta');

            try{
                DB::enableQueryLog();
                $confDias = Configuracion::ConfValidaTarjetaDias();
                $dias = $confDias->Valor;
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion ValidaPrestamoxTarjetaDias().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion ValidaPrestamoxTarjetaDias()', $error);
                return response()->json($error);
            }
            
            try{
                DB::enableQueryLog();
                $validacionPrestamoxTarjetaDias = Configuracion::ValidaPrestamoxTarjetaDias($tarjeta,$dias);
                if (count($validacionPrestamoxTarjetaDias) == 0) {
                    $validaPrestamoxTarjetaDias = 'error';            
                    return response()->json($validaPrestamoxTarjetaDias);
                }else{            
                    return response()->json($validacionPrestamoxTarjetaDias);
                }
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion ValidaPrestamoxTarjetaDias().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion ValidaPrestamoxTarjetaDias()', $error);
                return response()->json($error);
            }
            
        }else{
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'NO SE RECIBIO EL VALOR DE LA TARJETA EN LA FUNCION ValidaPrestamoxTarjetaDias()'                
            ];    
            Log::error('NO SE RECIBIO EL VALOR DE LA TARJETA EN LA FUNCION ValidaPrestamoxTarjetaDias()', $error);
            return response()->json($error);
        }               
    }

    public function getCatalogoSociedades(Request $request){        
        if(isset($request)){
            $SociedadID = $request->input('SociedadID');            
            try{
                DB::enableQueryLog();
                $catalogoSociedades = Configuracion::getCatalogoSociedades($SociedadID);                                
                return response()->json($catalogoSociedades);
            }catch(\Throwable $t){
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion getCatalogoSociedades().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion getCatalogoSociedades()', $error);
                return response()->json($error);
            }
        }else{
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'NO SE RECIBIO EL ID PARA EJECUTAR LA CONSULTA AL CATALOGO SOCIEDADES'
            ];    
            Log::error('NO SE RECIBIO EL ID PARA EJECUTAR LA CONSULTA AL CATALOGO SOCIEDADES', $error);
            return response()->json($error);
        }        
    }

    public function ConfLimiteReproceso(){        
        try{
            DB::enableQueryLog();
            $limiteReproceso = Configuracion::ConfLimiteReproceso();            
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion ConfLimiteReproceso().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion ConfLimiteReproceso()', $error);            
        }
        return intval($limiteReproceso->Valor);
    }

    public function getNumReference(){
        try{
            DB::enableQueryLog();
            $numReference = Configuracion::getNumReference();            
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getNumReference().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getNumReference()', $error);            
        }
        return $numReference->Valor;
    }

    public function buscaPrestamoPorID($PrestamoID){
        try{
            DB::enableQueryLog();            
            $prestamo = Configuracion::BuscarPrestamoPorID($PrestamoID);
            return $this->presToArray($prestamo, 0);

        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion buscaPrestamoPorID().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion buscaPrestamoPorID()', $error);            
        }        
    }
    
    public function swapTransfer(Request $request){        
        
        $validatorResponse = [];
        
        //Se aplica Validator para validacion en backend de las reglas de los campos del formulario
        $messages = [            
            'required' => 'El campo es requerido.',
            'inputClabeTarjeta.max' => 'El campo Roles no debe superar 1 caracter.',
            'inputCelular.max' => 'El campo debe tener máximo 7 digitos.',        
            'inputClabeTarjeta.digits_between' => 'El campo debe tener entre 16 y 18 digitos.',
        ];

        $validator = Validator::make($request->all(), [            
            'PrestamoID' => 'required',
            'inputServicio' => 'required',
            'banco' => 'required',
            'inputBanco2' => 'required',
            'inputClabeTarjeta' => 'required|digits_between:16,18',
            'inputCelular' => 'required|size:12',            
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

        
        $PrestamoID = (int) $request->input('PrestamoID');        
        try {
            DB::enableQueryLog();
            //Se Obtienen los datos del prestamo fallido
            $prestamoFallido = $this->buscaPrestamoPorID($PrestamoID);
        } catch (\Throwable $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion swapTransfer().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion swapTransfer()', $error);
            
            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        }

        $limiteReproceso = $this->ConfLimiteReproceso();
        $numReference = $this->getNumReference();
        
        $transferId = $prestamoFallido[0]->DPVale;// $request->transferId;        
        $rawTransferData['numReference'] = $numReference;        
        $BancoAnterior = $prestamoFallido[0]->Banco; //$request->input('BancoAnterior');
        $TarjetaClabeAnterior = $prestamoFallido[0]->TarjetaClabe; //$request->input('TarjetaClabeAnterior');
        $ServicioID = (int) $request->input('inputServicio'); //$request->input('ServicioID');
        $sociedad = $prestamoFallido[0]->Sociedad; //$request->input('sociedad');
                
        $desc1 = $prestamoFallido[0]->CodPlaza; //$prestamoFallido['CodPlaza'];
        $desc2 = $prestamoFallido[0]->TiendaID; //$prestamoFallido['TiendaID'];
        $description = $desc1 . $desc2;
        //$description = $request->input('description');        
        
        $account = $request->input('inputClabeTarjeta'); //$request->input('account');
        $amount = $prestamoFallido[0]->Importe; //$request->input('amount');
        $bank = $request->input('banco'); //$request->input('bank');
        $bancoID = (int) $request->input('inputBanco2'); //$request->input('bancoID');
        $owner = $prestamoFallido[0]->NombreCliente; //$request->input('owner');
        $ownerPhone = $request->input('inputCelular'); //$request->input('ownerPhone');        
        
        $UsuarioID = (int) session("IDuser");        

        $datos = compact(
            'sociedad',
            'transferId',
            'description',
            'account',
            'numReference',
            'amount',
            'bank',
            'owner',
            'ownerPhone'
        );

        //se convierte de string a float porque así lo solicita el servicio
        $datos['amount'] = (float) $datos['amount'];
        
        $dat = [
            'sociedad' => $datos['sociedad'],
            'transferId' => $datos['transferId']
        ];
        
        try {            
            //Se verifica el status del tramite inicial
            $resultStatusSwap = SaveLoan::getSwapTransfer($dat);

        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion swapTransfer() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $dat,
                'responseLog' => isset($resultStatusSwap) ? $resultStatusSwap : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion swapTransfer() al consumir el servicio', $error);
                        
            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        } 
                
        if (array_key_exists('ErrorMessage', $resultStatusSwap)) {
            
            $Mensaje = json_decode($resultStatusSwap['ErrorMessage']['Mensaje']);
            
            if ($Mensaje->error === 'Error: Transfer not found') { //Significa que el numero de vale esta disponible
                
                //Se procede a iterar el numero de vale adicionando un numero porque respondió estar returned
                for ($i=0; $i < $limiteReproceso; $i++) { 
            
                    //Se valida si es la primer iteracion del bucle, si no lo es empieza a adicionar 
                    //un numero consecutivo al final del numero de vale
                    if($i==0){
                        $datos['transferId'] = $transferId;
                    }else{
                        $datos['transferId'] = $transferId.$i;
                    }
                    
                    try {     
                        DB::enableQueryLog();                   
                        //Se prueba si el vale con la adicion del numero se encuentra libre
                        $dat = [
                            'sociedad' => $datos['sociedad'],
                            'transferId' => $datos['transferId']
                        ];

                        $resultStatusSwap = SaveLoan::getSwapTransfer($dat);
                        
                        if (array_key_exists('result', $resultStatusSwap)) {
                            
                            if (in_array($resultStatusSwap['result']['status'], ['paid', 'in_process', 'failed'])){
                                return response()->json([
                                    'valid' => 1,
                                    'status' => 1,
                                    'data' => $resultStatusSwap, 
                                ]);
                            }else if ($resultStatusSwap['result']['status'] === 'returned') {                                
                                continue;
                            }
                        }else if (array_key_exists('ErrorMessage', $resultStatusSwap)) {                            
                                                        
                            $Mensaje = json_decode($resultStatusSwap['ErrorMessage']['Mensaje']);
                            
                            if ($Mensaje->error === 'Error: Transfer not found') {//Significa que el numero de vale esta disponible
                                
                                //Se ejecuta el servicio de dispersión 
                                $response = SaveLoan::swapTransfer($datos);

                                if (array_key_exists('ErrorMessage', $response)) {                                                                
                                    continue; //Si la dispersión falla se salta al siguiente ciclo para adicionar otro numero al final del nomero de vale
                                }else if (array_key_exists('result', $response)) {                                    
                                    //Se valida si el servicio dió resultado
                                    if (in_array($response['result']['status'], ['paid', 'in_process'])){
                                        
                                        $dat = [
                                            'sociedad' => $datos['sociedad'],
                                            'transferId' => $datos['transferId']
                                        ];
                                        
                                        //Se verifica que la dispersión efectivamente fué satisfactoria ejecutando de nuevo getTransfer
                                        $res = SaveLoan::getSwapTransfer($dat);

                                        if (!$res) {                                    
                                            //Si la validación falla el proceso termina
                                            return response()->json([
                                                'valid' => 2,
                                                'status' => 0,                                
                                                'data' => $res, 
                                            ]);
                                        } else if (array_key_exists('ErrorMessage', $res)) {                                    
                                            //Se valida que la dispersión arrojo algun error y el proceso termina
                                            return response()->json([
                                                'valid' => 2,
                                                'status' => 0,                                
                                                'data' => $res, 
                                            ]); 
                                        } else if (array_key_exists('result', $res)) {                                                                
                                            
                                            //Finalmente se valida si el result es satisfactorio y termina el proceso
                                            if (in_array($res['result']['status'], ['paid', 'in_process', 'failed'])){
                                                
                                                //Se encriptan las tarjetas y se convierten a base64 antes de guardarlas
                                                $TarjetaClabeAnterior = base64_encode($this->descifrarTarjeta(0, date('Y-m-d'), $TarjetaClabeAnterior));
                                                $account = base64_encode($this->descifrarTarjeta(0, date('Y-m-d'), $account));
                                                
                                                //Se registra el intento en la Base de datos ejecutando SP Registrar Intento
                                                $resRegistroIntento = Configuracion::RegistrarIntento(
                                                    $PrestamoID, 
                                                    $transferId, 
                                                    $BancoAnterior, 
                                                    $TarjetaClabeAnterior, 
                                                    $i - 1, 
                                                    $bank,
                                                    $account,
                                                    $UsuarioID,
                                                    $ServicioID,
                                                    $bancoID,
                                                    $res['result']['status']
                                                );                                        
                                                
                                                if ($resRegistroIntento[0]->status == '1') {
                                                    //Retorna retorna valor positivo
                                                    return response()->json([
                                                        'valid' => 2,
                                                        'status' => 1,
                                                        'data' => $res, 
                                                    ]);                
                                                }else{
                                                    //Retorna cualquier otro resultado arrojado                                                    
                                                    return response()->json([
                                                        'valid' => 2,
                                                        'status' => 0,                                
                                                        'data' => $res, 
                                                    ]);
                                                }                
                                            }else{
                                                //Retorna cualquier otro resultado arrojado
                                                return response()->json([
                                                    'valid' => 2,
                                                    'status' => 0,                                
                                                    'data' => $res, 
                                                ]);                                        
                                            }                                    
                                        }
                                    }else if (in_array($response['result']['status'], ['failed'])){
                                        return response()->json([
                                            'valid' => 2,
                                            'status' => 0,                                
                                            'data' => $response, 
                                        ]);
                                    }
                                }else{
                                    return response()->json([
                                        'valid' => 1,
                                        'status' => 0,
                                        'data' => $response, 
                                    ]);
                                }
                            }else{
                                return response()->json([
                                    'valid' => 1,
                                    'status' => 0,
                                    'data' => $resultStatusSwap, 
                                ]);
                            }
                        }else{
                            return response()->json([
                                'valid' => 1,
                                'status' => 0,
                                'data' => $resultStatusSwap, 
                            ]);
                        }

                    } catch (\Throwable $t) {
                        $queries = DB::getQueryLog();
                        $lastQuery = end($queries); // Obtener la última consulta ejecutada
                        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                        $requestLog = $lastQuery['query']; // SQL de la consulta
                        $error = [
                            'status' => '0',
                            'fecha' => date('Y-m-d H:i:s'),
                            'descripcion' => 'Error en la funcion swapTransfer().',
                            'codigoError' => $t->getCode(),
                            'msnError' => $t->getMessage(),
                            'linea' => $t->getLine(),
                            'archivo' => $t->getFile(),
                            'requestLog' => [$requestLog, $dat], // Agregar el SQL al log
                            'responseLog' => [$bindings, $resultStatusSwap], // Agregar los parámetros al log
                        ];
                        Log::error('Error en la funcion swapTransfer()', $error);
                                                
                        return response()->json([
                            'status' => 0,
                            'message' => $t->getMessage(),
                            'data' => [], 
                        ]);
                    } 
                    
                }
                //Si se alcanzan el numero de reprocesos permitidos
                return response()->json([
                    'valid' => 1,
                    'status' => 0,
                    'data' => $resultStatusSwap,
                    'intentos' => $limiteReproceso
                ]);

            }else{
                return response()->json([
                    'valid' => 1,
                    'status' => 1,
                    'data' => $resultStatusSwap, 
                ]);
            }            
        }else if (array_key_exists('result', $resultStatusSwap)) {             
            if(in_array($resultStatusSwap['result']['status'], ['paid', 'in_process', 'failed'])){                
                return response()->json([
                    'valid' => 1,
                    'status' => 1,
                    'data' => $resultStatusSwap, 
                ]);                
            }else if ($resultStatusSwap['result']['status'] == 'returned') {
                
                //Se procede a iterar el numero de vale adicionando un numero porque respondió estar returned
                for ($i=1; $i < $limiteReproceso + 1; $i++) { 
            
                    //Se adiciona un numero consecutivo al final del numero de vale
                    $datos['transferId'] = $transferId.$i;
                    
                    try {   
                        DB::enableQueryLog();                     
                        //Se prueba si el vale con la adicion del numero se encuentra libre
                        $dat = [
                            'sociedad' => $datos['sociedad'],
                            'transferId' => $datos['transferId']
                        ];

                        $resultStatusSwap = SaveLoan::getSwapTransfer($dat);
                        
                        if (array_key_exists('result', $resultStatusSwap)) {
                            
                            if (in_array($resultStatusSwap['result']['status'], ['paid', 'in_process', 'failed'])){
                                return response()->json([
                                    'valid' => 1,
                                    'status' => 1,
                                    'data' => $resultStatusSwap, 
                                ]);
                            }else if ($resultStatusSwap['result']['status'] === 'returned') {                                
                                continue;
                            }
                        }else if (array_key_exists('ErrorMessage', $resultStatusSwap)) {                            
                                                        
                            $Mensaje = json_decode($resultStatusSwap['ErrorMessage']['Mensaje']);
                            
                            if ($Mensaje->error === 'Error: Transfer not found') {//Significa que el numero de vale esta disponible
                                
                                //Se ejecuta el servicio de dispersión 
                                $response = SaveLoan::swapTransfer($datos);

                                if (array_key_exists('ErrorMessage', $response)) {                                                                
                                    continue; //Si la dispersión falla se salta al siguiente ciclo para adicionar otro numero al final del nomero de vale
                                }else if (array_key_exists('result', $response)) {                                    
                                    //Se valida si el servicio dió resultado
                                    if (in_array($response['result']['status'], ['paid', 'in_process'])){
                                        
                                        $dat = [
                                            'sociedad' => $datos['sociedad'],
                                            'transferId' => $datos['transferId']
                                        ];
                                        
                                        //Se verifica que la dispersión efectivamente fué satisfactoria ejecutando de nuevo getTransfer
                                        $res = SaveLoan::getSwapTransfer($dat);

                                        if (!$res) {                                    
                                            //Si la validación falla el proceso termina
                                            return response()->json([
                                                'valid' => 2,
                                                'status' => 0,                                
                                                'data' => $res, 
                                            ]);
                                        } else if (array_key_exists('ErrorMessage', $res)) {                                    
                                            //Se valida que la dispersión arrojo algun error y el proceso termina
                                            return response()->json([
                                                'valid' => 2,
                                                'status' => 0,                                
                                                'data' => $res, 
                                            ]); 
                                        } else if (array_key_exists('result', $res)) {                                                                
                                            
                                            //Finalmente se valida si el result es satisfactorio y termina el proceso
                                            if (in_array($res['result']['status'], ['paid', 'in_process', 'failed'])){
                                                
                                                //Se encriptan las tarjetas y se convierten a base64 antes de guardarlas
                                                $TarjetaClabeAnterior = base64_encode($this->descifrarTarjeta(0, date('Y-m-d'), $TarjetaClabeAnterior));
                                                $account = base64_encode($this->descifrarTarjeta(0, date('Y-m-d'), $account));
                                                
                                                //Se registra el intento en la Base de datos ejecutando SP Registrar Intento
                                                $resRegistroIntento = Configuracion::RegistrarIntento(
                                                    $PrestamoID, 
                                                    $transferId, 
                                                    $BancoAnterior, 
                                                    $TarjetaClabeAnterior, 
                                                    $i - 1, 
                                                    $bank,
                                                    $account,
                                                    $UsuarioID,
                                                    $ServicioID,
                                                    $bancoID,
                                                    $res['result']['status']
                                                );                                        
                                                
                                                if ($resRegistroIntento[0]->status == '1') {
                                                    //Retorna retorna valor positivo
                                                    return response()->json([
                                                        'valid' => 2,
                                                        'status' => 1,
                                                        'data' => $res, 
                                                    ]);                
                                                }else{
                                                    //Retorna cualquier otro resultado arrojado                                                    
                                                    return response()->json([
                                                        'valid' => 2,
                                                        'status' => 0,                                
                                                        'data' => $res, 
                                                    ]);
                                                }                
                                            }else{
                                                //Retorna cualquier otro resultado arrojado
                                                return response()->json([
                                                    'valid' => 2,
                                                    'status' => 0,                                
                                                    'data' => $res, 
                                                ]);                                        
                                            }                                    
                                        }
                                    }else if (in_array($response['result']['status'], ['failed'])){
                                        return response()->json([
                                            'valid' => 2,
                                            'status' => 0,                                
                                            'data' => $response, 
                                        ]);
                                    }
                                }else{
                                    return response()->json([
                                        'valid' => 1,
                                        'status' => 0,
                                        'data' => $response, 
                                    ]);
                                }
                            }else{
                                return response()->json([
                                    'valid' => 1,
                                    'status' => 0,
                                    'data' => $resultStatusSwap, 
                                ]);
                            }
                        }else{
                            return response()->json([
                                'valid' => 1,
                                'status' => 0,
                                'data' => $resultStatusSwap, 
                            ]);
                        }

                    } catch (\Throwable $t) {
                        $queries = DB::getQueryLog();
                        $lastQuery = end($queries); // Obtener la última consulta ejecutada
                        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                        $requestLog = $lastQuery['query']; // SQL de la consulta
                        $error = [
                            'status' => '0',
                            'fecha' => date('Y-m-d H:i:s'),
                            'descripcion' => 'Error en la funcion swapTransfer().',
                            'codigoError' => $t->getCode(),
                            'msnError' => $t->getMessage(),
                            'linea' => $t->getLine(),
                            'archivo' => $t->getFile(),
                            'requestLog' => [$requestLog, $dat], // Agregar el SQL al log
                            'responseLog' => [$bindings, $resultStatusSwap], // Agregar los parámetros al log
                        ];
                        Log::error('Error en la funcion swapTransfer()', $error);
                        
                        return response()->json([
                            'status' => 0,
                            'message' => $t->getMessage(),
                            'data' => [], 
                        ]);
                    } 
                    
                }
                //Si se alcanzan el numero de reprocesos permitidos
                return response()->json([
                    'valid' => 1,
                    'status' => 0,
                    'data' => $resultStatusSwap,
                    'intentos' => $limiteReproceso
                ]);
            }            
        }else if (!$resultStatusSwap || gettype($$resultStatusSwap) !== 'object') {
            Log::error('Error al validar status de trámite getSwapTransfer');
            return response()->json([
                'status' => 0,                                
                'data' => $resultStatusSwap, 
            ]);
        }       
        
    }    
    
}
