<?php

namespace App\Http\Controllers\Home;

use Auth;
use Session;
use DateTime;
use DateTimeZone;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\Configuracion;
use App\Models\SaveLoan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use App\Services\EncryptService;
use App\Services\SessionService;
use App\Services\ConfigService;
use Carbon\Carbon;
use App\Services\PreCargaPreDirectosService;





class ValeFinancieroController extends Controller
{
    private $url_broker;
    private $path_pos_s2credit;
    private $path_s2credit_api;
    private $encryptService;
    private $sessionService;  
    private $path_sms;
    private $request;

    public function __construct(EncryptService $encryptService, SessionService $sessionService, Request $request)  
    {
        $this->url_broker = Configuracion::obtenerValorPorParametro('url_broker');
        $this->path_pos_s2credit = Configuracion::obtenerValorPorParametro('path_pos_s2credit');
        $this->path_s2credit_api = Configuracion::obtenerValorPorParametro('path_s2credit_api');
        $this->path_sms = Configuracion::obtenerValorPorParametro('path_sms');
        $this->encryptService = $encryptService;
        $this->sessionService = $sessionService; 
        $this->request = $request;


    }
    public function index(Request $request)   // Añade Request $request como argumento aquí
        {          

            
            $sessionLifetime = $this->sessionService->getSessionLifetime($request);

            //imprimir toda la data de sesion
            // dd($request->session()->all());
            // dd(session("IDuser"));throw new \Exception("Esto es una excepción simulada.");
            $routeName = $this->request->route()->getName();

        $selectedInsurance = null;
        // try{
            
        //     DB::enableQueryLog();

            $seguroId = Configuracion::obtenerValorPorParametro('SeguroID');
            // dd($seguroId);
                    // $results = DB::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
            // $results =[];
    
            // Puedes agregar una verificación para los resultados vacíos si es necesario, como hicimos en el caso anterior
        //     if (empty($seguroId)) {
        //         return ['error' => 'No se encontró la configuración. Contacte al administrador'];
        //     }
        //     return $seguroId;
        // } catch (\PDOException $e) {
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta
        //     $error = [
        //         'status' => '0',
        //         'fecha' => date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la función getTienda() del Modelo ReporteLogsService',
        //         'codigoError' => $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog,
        //         'responseLog' => $bindings,
        //     ];
        //     Log::error('Error en la función getTienda() del Modelo ReporteLogsService', $error);
        // }catch(\Throwable $t){
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta
        //     $error = [
        //         'status' => '0',
        //         'fecha' => date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la funcion obtenerValorPorParametro("SeguroID").',
        //         'codigoError' => $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog, // Agregar el SQL al log
        //         'responseLog' => $bindings, // Agregar los parámetros al log
        //     ];
        //     Log::error('Error en la funcion obtenerValorPorParametro("SeguroID")', $error);
        // }
        
        $response = $this->getInsurance();
        $insurances = $this->getInsurance();
            // dd($response);
        // dd($insurances);
        $catalogoIdentificaciones = Configuracion::getCatalogoIdentificaciones();

        // try {
        //     DB::enableQueryLog(); // Habilitar el registro de consultas
        
            // dd($catalogoIdentificaciones);
                // dd($response);
            if ($response && array_key_exists('data', $response)) {
                $insuranceData = $response['data'];
                $filteredInsuranceData = array_filter($insuranceData, function ($insuranceItem) use ($seguroId) {
                    return $insuranceItem['id_insurance'] == $seguroId;
                });
                // dd($insuranceData,$filteredInsuranceData);
    
                if (count($filteredInsuranceData) > 0) {
                    $selectedInsurance = array_values($filteredInsuranceData)[0];
                } else {
                    $selectedInsurance = null;
                }
            
            } else {
                $insuranceError = 'Hubo un problema al obtener los seguros, favor de reportarlo con el administrador del sistema';
            }
            // dd($selectedInsurance,$insuranceData,$filteredInsuranceData);
        // } catch (\Throwable $t) {
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta
        
        //     $error = [
        //         'status' => '0',
        //         'fecha' => date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la Consulta del catálogoIdentificaciones',
        //         'codigoError' => $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog, // Agregar el SQL al log
        //         'responseLog' => $bindings, // Agregar los parámetros al log
        //     ];
        
        //     Log::error('Error en la Consulta del catálogoIdentificaciones', $error);
        // }
        
        // dd($catalogoIdentificaciones);
        // if ($response && array_key_exists('data', $response)) {
        //     $insuranceData = $response['data'];
        //     $filteredInsuranceData = array_filter($insuranceData, function ($insuranceItem) use ($seguroId) {
        //         return $insuranceItem['id_insurance'] == $seguroId;
        //     });

        //     if (count($filteredInsuranceData) > 0) {
        //         $selectedInsurance = array_values($filteredInsuranceData)[0];
        //     } else {
        //         $selectedInsurance = null;
        //     }
        
        // } else {
        //     $insuranceError = 'Hubo un problema al obtener los seguros, favor de reportarlo con el administrador del sistema';
        // }

        // try {
        //     DB::enableQueryLog(); // Habilitar el registro de consultas
        
            $identificacionesId = Configuracion::obtenerValorPorParametro('IdentificacionesID');
        
        // } catch (\Throwable $t) {
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta
        
        //     $error = [
        //         'status' => '0',
        //         'fecha' => date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la funcion obtenerValorPorParametro("IdentificacionesID")',
        //         'codigoError' => $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog, 
        //         'responseLog' => $bindings, 
        //     ];
        
        //     Log::error('Error en la funcion obtenerValorPorParametro("IdentificacionesID")', $error);
        // }
        
        
        $identificationResponse = $this->getIdentifications();

        $selectedIdentification = null;
        $identificationData = null;
        
        if (is_array($identificationResponse)) {
            $identificationError = 'getIdentifications() ha devuelto un array en lugar de un objeto de respuesta.';
        } elseif (is_object($identificationResponse) && method_exists($identificationResponse, 'successful')) {
            if ($identificationResponse->successful()) {
                // Tu código existente aquí
            } else {
                $identificationError = 'Hubo un problema al obtener las identificaciones, favor de reportarlo con el administrador del sistema.';
            }
        } else {
            $identificationError = 'getIdentifications() ha devuelto un tipo de dato inesperado o un objeto sin el método successful().';
        }
        
        
        if (isset($identificationError)) {
            // Manejar el error, por ejemplo:
            // Log::error($identificationError);
            // return redirect()->back()->withErrors([$identificationError]);
        }
        
        $branches = $this->getBranches();
        $message = null;

        $Plazas = null;
        if (isset(session("USERDATA")["Plazas"])) {
            $Plazas = session("USERDATA")["Plazas"];
        } elseif (isset(session("USERDATA")["CodPlaza"])) {
            $Plazas = session("USERDATA")["CodPlaza"];
        }
                
        // dd($Plazas);
        $filteredBranch = null;

        if ($branches === false) {
            $message = [
                'type' => 'error',
                'text' => 'Ocurrió un error al obtener el catálogo de plazas. Favor de volver a intentar'
            ];
        } else {
            if (isset($branches['data'])) {
                foreach ($branches['data'] as $branch) {
                    if ($branch['code'] === $Plazas) {

                        $filteredBranch = $branch;
                        break;
                    }
                }
            }

            if ($filteredBranch === null) {
                $message = [
                    'type' => 'warning',
                    'text' => 'No se encontraron plazas registradas para el trámite. Favor de verificar'
                ];
            }
        }
        // dd($filteredBranch);
        if ($filteredBranch) {
            $interestResponse = $this->getInterestByBranch($filteredBranch['id']);
            // dd($interestResponse);
        } else {
            $interestResponse = false;
            
        }
        
        if ($interestResponse === false) {
            $message = [
                'type' => 'error',
                'text' => 'Ocurrió un error al obtener los intereses. Favor de volver a intentar'
            ];
        }
        
        $catalogoServicios = $this->getCatalogoServicios();
        $message = null;
    
        if ($catalogoServicios === false) {
            $message = [
                'type' => 'error',
                'text' => 'Ocurrió un error al obtener el catalogo de servicios. Favor de volver a intentar'
            ];
        } elseif (empty($catalogoServicios)) {
            $message = [
                'type' => 'warning',
                'text' => 'No se encontraron servicios registrados para la plaza enviada. Favor de verificar'
            ];
        }
        // try {
        //     DB::enableQueryLog(); // Habilitar el registro de consultas

            $bancos = Configuracion::getCatalogoBancos();
            // dd($bancos);
            // if (count($bancos) == 0) {
            //     $errorBancos = 'No se encontraron registros de Bancos';
            // } else {
            //     $errorBancos = null;
            // }
    
        // } catch (\Throwable $t) {
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta

        //     $error = [
        //         'status'=> '0',
        //         'fecha'=> date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la consulta getCatalogoBancos',                
        //         'codigoError'=> $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog,
        //         'responseLog' => $bindings
        //     ];    
        //     Log::error('Error en la consulta getCatalogoBancos', $error);
            
        //     $errorBancos = 'Ocurrió un error al obtener el catálogo de Bancos';
        // }
        // try {
        //     DB::enableQueryLog(); // Habilitar el registro de consultas
            $vcBines = Configuracion::getBinesBancarios();
    
            if (count($vcBines) == 0) {
                $errorBines = 'No se encontraron registros de Bines';
            } else {
                $errorBines = null;
            }
    
        // } catch (\Throwable $t) {
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta
            
        //     $error = [
        //         'status'=> '0',
        //         'fecha'=> date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la consulta getBinesBancarios',                
        //         'codigoError'=> $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog,
        //         'responseLog' => $bindings
        //     ];    
        //     Log::error('Error en la consulta getBinesBancarios', $error);
            
        //     $errorBines = 'Ocurrió un error al obtener el catálogo de Bines';
        // }
        $storesResponse = $this->getStores($Plazas);
        $errorStores = isset($storesResponse['error']) ? $storesResponse['error'] : null;
        // try{
            // DB::enableQueryLog(); // Habilitar el registro de consultas
            $fechaServidor = Configuracion::getFechaServidor();
        // }catch (\Throwable $t){
        //     $queries = DB::getQueryLog();
        //     $lastQuery = end($queries); // Obtener la última consulta ejecutada
        //     $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        //     $requestLog = $lastQuery['query']; // SQL de la consulta

        //     $error = [
        //         'status'=> '0',
        //         'fecha'=> date('Y-m-d H:i:s'),
        //         'descripcion' => 'Error en la consulta getBinesBancarios',                
        //         'codigoError'=> $t->getCode(),
        //         'msnError' => $t->getMessage(),
        //         'linea' => $t->getLine(),
        //         'archivo' => $t->getFile(),
        //         'requestLog' => $requestLog,
        //         'responseLog' => $bindings
        //     ];    
        //     Log::error('Error en la consulta getBinesBancarios', $error);
        // }        
        $Plazas = session("USERDATA")["CodPlaza"];
        // dd($fechaServidor);
        $vTiendaID =$this->getStores($Plazas);
        $relationships = $this->searchRelationship();
        $title = "Vale Financiero";
        
        $tabuladorPagosPath = Configuracion::obtenerValorPorParametro('path_tabuladorPagos');
        // dd($tabuladorPagosPath);

        $esquemaRevolvente = Configuracion::obtenerValorPorParametro('path_esquemaRevolvente');

        return view('home.aplicaciones.valeFinanciero.index', 
            compact('catalogoServicios', 'message', 'filteredBranch', 'interestResponse', 
            'selectedIdentification', 'identificationData', 'identificacionesId', 'bancos', 
            'vcBines', 'errorBines', 'storesResponse', 'errorStores', 'fechaServidor', 'catalogoIdentificaciones', 
            'selectedInsurance','insurances','vTiendaID','relationships', 'title','sessionLifetime','tabuladorPagosPath',
            'esquemaRevolvente','branches','identificationResponse'));

    }
    
    public function getCatalogoServicios()
    {
        $Plazas = session("USERDATA")["CodPlaza"];     
        try{
            DB::enableQueryLog(); // Habilitar el registro de consultas
            $results = DB::select("EXEC dbo.getCatalogoServicios @Codplaza = ?", [$Plazas]);   
                    // $results =[];
        // $results = db::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
        // dd($results);
        if(empty($results)){
            return ['error' => 'No se encontraron Servicios registrados para la plaza. Contacte al administrador.'];
        }
        return $results;         
    } catch (\PDOException $e) {
        $errorMessage = PreCargaPreDirectosService::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL.','Ocurrió un error al conectar con la base de datos SQL.');
        return $errorMessage;

        }catch (\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la consulta getCatalogoServicios',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la consulta getCatalogoServicios', $error);            
        }

        return $result;
        
    }
    public function showSecondScreen()
    {
        return view('home.aplicaciones.secondScreen.extendedScreen_v');
    }


    public function getBranches()
    {
        $requestData = [ 
            'branches' => []
        ];
        $response = null; 

        try {

            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
                        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
                // dd($response);
                if(empty($response)){
                    return ['error' => 'No se encontraron plazas registradas. Contacte al administrador.'];
                }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función getBranches() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función getBranches() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar plazas, favor de volver a intentar.'];

        } catch (\Throwable $t){

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getBranches() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getBranches() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }

    
        return false;
    }


    public function getBranchesData()
    {
        $branchesResponse = null;  // Inicialización aquí
    
        try {
            $branchesResponse = $this->getBranches();
            // dd($branchesResponse);
        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getBranchesData()',                
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $branchesResponse,   
                'responseLog' => $branchesResponse
            ];    
            Log::error('Error en la funcion getBranchesData()', $error);            
        }
    
        if ($branchesResponse && array_key_exists('data', $branchesResponse)) {
            return $branchesResponse['data'];
        }
    
        return false;
    }
    

    public function getInterestByBranch($id_branch)
    {   
        $requestData = [
            'interest-by-branch' => [
                'id_branch' => $id_branch
            ]
        ];
        $response = null;
        try {
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
                        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
                // dd($response);
                if(empty($response)){
                    return ['error' => 'No se encontraron intereses registrados en la plaza. Contacte al administrador.'];
                }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función getInterestByBranch() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función getInterestByBranch() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar el interés por plaza, favor de volver a intentar.'];

        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getInterestByBranch() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getInterestByBranch() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }
    
        return false;
    }
    public function getInsurance()
    {
        $requestData = [
            'insurance' => []
        ];
        $response = null;
        try {
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'insurance' => []
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
            // dd($response);
            // $response = [];
            // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            if (empty($response)) {
                return ['error' => 'No se encontró información del seguro. Contacte al administrador'];
            }
            // return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar el seguro, favor de volver a intentar.'];

        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getInsurance() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getInsurance() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            $responseData = $response->json();
    
            if (is_array($responseData) && array_key_exists('data', $responseData)) {
                return $responseData;
            }
    
            return [];
        }
    
        return false;
    }


    public function getIdentifications()
        {
            $requestData = [
                'identifications' => []
            ];
            $response = null;
            try {
                // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
                //     'identifications' => []
                // ]);
                $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
                // dd($response->body());
                // $response = [];
            // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            if (empty($response)) {
                return ['error' => 'No se encontraron identifciaciones cargadas. Contacte al administrador'];
            }
            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar el catalogo de identificaciones, favor de volver a intentar.'];

            } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getIdentifications() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getIdentifications() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            $responseData = $response->json();
    
            if (is_array($responseData) && array_key_exists('data', $responseData)) {
                return $responseData;
            }
    
            return [
                'error' => 'Hubo un problema, favor de reportarlo con el administrador del sistema'
            ];
        }
    
        return false;
    }

    
    public function findStoreIdByCode($code, $stores)
    {  
        foreach ($stores as $store) {
            if (is_array($store) && array_key_exists('code', $store) && array_key_exists('id', $store)) {
                if ($store['code'] == $code) {
                    return $store['id'];
                }
            }
        }
    
        return null;
    }
    
    public function getStores($Plazas)
    {
        // dd(session()->all());
        $branches = $this->getBranchesData();
        // dd($branches);
        $branchId = $this->findBranchIdByCode($Plazas, $branches);
        // dd($branchId, $branches);
        $TiendaOVTA= session("USERDATA")["TiendaOVTA"];
        if ($branchId === null) {
            return ['error' => 'No se encontró una sucursal con el código proporcionado.'];
        }
        $requestData = [
            'store' => [
                'id_branch' => $branchId
            ]
        ];
        $response = null;
        try {
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'store' => [
            //         'id_branch' => $branchId
            //     ]
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
            // dd($response->body());
            // $response = [];
        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
        if (empty($response)) {
            return ['error' => 'No se encontraron tiendas cargadas para la plaza. Contacte al administrador'];
        }
        // return $response;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al buscar el catalogo de tiendas, favor de volver a intentar.'];

        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getStores() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
            ];    
            Log::error('Error en la funcion getStores() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al obtener el catálogo de Tiendas.'];
        }
    // dd($response);
        if ($response->successful()) {
            $data = $response->json();
            // dd($data);
            if (empty($data)) {
                return ['error' => 'No se encontraron registros de Tiendas.'];
            }
            
            $vTiendaID = $this->findStoreIdByCode($TiendaOVTA, $data['data']);
       
            if ($vTiendaID === null) {
                return ['error' => 'No se encontró en la plaza '. $Plazas . ' una tienda con el código ' . $TiendaOVTA . '.'];
            }
    
            return $this->findStoreIdByCode($TiendaOVTA, $data['data']);
    
        }
    
        return ['error' => 'Ocurrió un error al obtener el catálogo de Tiendas.'];
    }
    
    public function findBranchIdByCode($Plazas, $branches)
    {
        
        if (is_array($branches)) {
            foreach ($branches as $branch) {
                if (array_key_exists('code', $branch) && array_key_exists('id', $branch) && $branch['code'] === $Plazas) {
                    return $branch['id'];
                }
            }
        }
    
        return null;
    }
    
    public function getCouponSearch()
    {
        $coupon = request('coupon');
    
        $data = [
            'coupon-search' => [
                'coupon' => $coupon
            ]
        ];
        $response = null;
        try {
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $data);
            // dd($response->body());
            // dd($response);
            // $response = [];
        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
        if (empty($response)) {
            return response()->json(['error' => 'No se encontraron datos del vale.'], 404); // 204 No Content, or 404 Not Found
        }
        // return $response;
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al buscar el catalogo de tiendas, favor de volver a intentar.'];
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCouponSearch() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'dpVale' => $coupon,
                'requestLog' => $data,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getCouponSearch() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }
    
        return false;
    }
    
    public function searchCustomer()
    {
        $customer = request('customer');
        $coupon = request('coupon');
    
        if (!$customer) {
            return false;
        }
    
        $requestData = [
            'search-customer' => [
                'data' => $customer
            ]
        ];
        $response = null;
        try {
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
            // dd($response->body());
            // $response = [];
        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
        if (empty($response)) {
            return response()->json(['error' => 'No se encontraron datos del cliente.'], 404); // 204 No Content, or 404 Not Found
        }
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al buscar el catalogo de tiendas, favor de volver a intentar.'];

        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion searchCustomer() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'dpVale' => $coupon,
                'requestLog' => $data,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion searchCustomer() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }
    
        return false;
    }
    
    public function searchRelationship()
    {
        $requestData = [
            'relationship' => []
        ];
        $response = null;
        try {
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'relationship' => []
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion searchRelationship() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion searchRelationship() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }
    
        return false;
    }
    
    public function enviarSMS(Request $request) // aqui
    {
        $mensaje = $request->input('mensaje');
        $telefonos = $request->input('telefonos');

        if (!is_array($telefonos)) {
            $telefonos = [$telefonos];
        }

        $url = $this->url_broker . $this->path_sms;

        $requestData = [
            'mensaje' => $mensaje,
            'telefonos' => $telefonos
        ];
        // dd($mensaje, $telefonos, $url, $requestData);
        $response = null;
        try {
        // $response = Http::post($url, [
        //         'mensaje' => $mensaje,
        //         'telefonos' => $telefonos
        //     ]);
                                        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
            $response = Http::post($url, $requestData);
            if(empty($response)){
                return response()->json(['error' => 'No se obtuvo respuesta por parte del servicio. Contacte al administrador.'], 404);
            }
            
    // return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función getBranches() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función getBranches() al consumir el servicio s2credit', $error);
            return response()->json(['error' => 'Ocurrió un error al conectarse con el servicio, favor de volver a intentar. Contacte al administrador.'], 404);
            
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion enviarSMS() al consumir el servicio EnviarSMS',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion enviarSMS() al consumir el servicio EnviarSMS', $error);
            return response()->json(['status' => 'error', 'message' => 'Error al enviar el SMS']);
        }
    
        if ($response->successful()) {
            return response()->json(['status' => 'success', 'data' => $response->json()]);
        }
    
        return response()->json(['status' => 'error', 'message' => 'Error al enviar el SMS']);
    }
    


    public function getPeps(Request $request)
    {
        $idCustomer = $request->input('customer');
        $folio = $request->input('folio');
        $requestData = [
            'idCustomer' => $idCustomer,
            'folio' => $folio
        ];
        $response = null;
        try {
            // $response = Http::post($this->url_broker . $this->path_s2credit_api, [
            //     'idCustomer' => $idCustomer,
            //     'folio' => $folio
            // ]);
            // dd($this->url_broker . $this->path_s2credit_api);
            // dd(json_encode($requestData, JSON_PRETTY_PRINT));
            $response = Http::post($this->url_broker . $this->path_s2credit_api, $requestData);
                        // dd($response->body());
            // $response = [];
        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
        if (empty($response)) {
            return response()->json(['error' => 'No se encontraron datos del cliente.'], 404); // 204 No Content, or 404 Not Found
        }
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $requestData,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al consultar el servicio Peps, favor de volver a intentar.'];

            
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getPeps() al consumir el servicio s2credit peps',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'dpVale' => $folio,
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getPeps() al consumir el servicio s2credit peps', $error);
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al consultar al cliente en lista Peps, favor de volver a intentar.'
            ]);
        }
    
        if ($response->successful()) {
            $data = json_decode($response->body());
    
            if ($data->status === '1' || $data->status === '0') {
                return response()->json([
                    'status' => $data->status,
                    'message' => $data->msn
                ]);
            } else {
                return response()->json([
                    'status' => $data->status,
                    'message' => $data->msn
                ]);
            }
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'Ocurrió un error al consultar al cliente en lista Peps, favor de volver a intentar.'
        ]);
    }
    public function obtenerImprimirDocs() {

        try{
            DB::enableQueryLog(); // Habilitar el registro de consultas
            return Configuracion::obtenerValorPorParametro('ConfigAIOGlobal.ImprirmirDocs');
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la consulta obtenerValorPorParametro("ConfigAIOGlobal.ImprirmirDocs")',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la consulta obtenerValorPorParametro("ConfigAIOGlobal.ImprirmirDocs")', $error);            
        }        
    }


    public function getFechaServidor() {
        try{
            DB::enableQueryLog(); // Habilitar el registro de consultas
            $fechaServidor = Configuracion::getFechaServidor();            
        }catch(\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getFechaServidor()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion getFechaServidor()', $error);            
        }

        return response()->json(['fechaServidor' => $fechaServidor]);        
    }

    public function getBinesBancarios()
    {
        try {
            DB::enableQueryLog(); // Habilitar el registro de consultas
            $vcBines = Configuracion::getBinesBancarios();
    
            if (count($vcBines) == 0) {
                return response()->json(['error' => 'No se encontraron registros de Bines'], 404);
            } else {
                return response()->json($vcBines);
            }
        } catch (\Throwable $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la consulta getBinesBancarios()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la consulta getBinesBancarios()', $error);
            return response()->json(['error' => 'Ocurrió un error al obtener el catálogo de Bines, favor de volver a intentar.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al obtener el catálogo de Bines'], 500);
        }
    }


    public function validarMonto(Request $request) //AQUI ACTUAL
    {


        $montoPrestamo = $request->input('textoSeleccionado');
        $interesResultado = $request->input('interestResult');
        $clienteDisponible = $request->input('customer_available');
        $seguroSeleccionado = $request->input('selectedInsurance');
    
        $seguroEncontrado = null;
        if (is_array($seguroSeleccionado) && array_key_exists('id_insurance', $seguroSeleccionado)) {
            $seguroEncontrado = $seguroSeleccionado;
        }
    
        try {
            if ($seguroEncontrado != null) {
                $tasaSeguro = $seguroEncontrado['rate'] * $request->input('numeroDeQuincenas');
                $montoTotalTramite = $montoPrestamo + $interesResultado + $tasaSeguro;
            } else {
                $montoTotalTramite = $montoPrestamo + $interesResultado;
            }
    
            if ($clienteDisponible !== null && $clienteDisponible < $montoTotalTramite) {
                return [
                    'error' => [
                        'error' => true, 
                        'message' => 'El cliente alcanzó su tope de compra', 
                        'disponible' => $clienteDisponible
                    ],
                    'montoTotal' => $montoTotalTramite
                ];
            } else {
                return [
                    'error' => [
                        'error' => false
                    ],
                    'montoTotal' => $montoTotalTramite
                ];
            }
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion validarMonto()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $getMessage,
                'responseLog' => $getFile
            ];    
            Log::error('Error en la funcion validarMonto()', $error);
            return response()->json(['error' => 'Ocurrió un error al validar el monto, favor de volver a intentar.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al validar el monto'], 500);
        }
    }
    
    public function saveLoanController(Request $request)
    {
        $folio = $request->input('folio');
        $cliente = $request->input('cliente');
        $distribuidor = $request->input('distribuidor');
        $montoPrestamo = $request->input('montoPrestamo');
        $quincenas = $request->input('quincenas');
        $idServicio = $request->input('idServicio');
        $idBanco = $request->input('idBanco');
        $service_value = $request->input('tarjetaClabe');
        $interest = $request->input('interestResult');
        $identificacion = $request->input('identificacion');
        $inputIdentificacion = $request->input('inputIdentificacion');
        $Plazas = session("USERDATA")["CodPlaza"];
        $vTiendaID = $this->getStores($Plazas);
        // dd($vTiendaID);
        $tienda = $request->input('tienda');
        $seguroId = Configuracion::obtenerValorPorParametro('SeguroID');
    
        $data = [
            "save-loan" => [
                "id_branch" => $tienda,
                "id_store" => $vTiendaID, 
                "id_customer" => $cliente, 
                "id_distributor" => $distribuidor, 
                "id_coupon" => $folio,
                "id_service" => $idServicio, 
                "id_bank" => $idBanco,
                "id_cell_provider" => "", 
                "transfer" => "0",
                "service_value" => $service_value, 
                "secure_code" => "",
                "interest" => $interest, 
                "id_identification" => $identificacion, 
                "identification_value" => $inputIdentificacion, 
                "id_amount" => $montoPrestamo,
                "id_fortnight" => $quincenas, 
                "id_relationship" => "", 
                "id_insurance" => $seguroId, 
                "beneficiary_name" => "",
                "beneficiary_middle_name" => "",
                "beneficiary_last_name" => "",
                "beneficiary_second_last_name" => ""
            ]
        ];
        // dd(json_encode($data, JSON_PRETTY_PRINT));
        $response = null;
        try {
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $data);
            // dd($response);
                        // $response = [];
        // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
        if (empty($response)) {
            return response()->json(['error' => 'No se encontraron datos del cliente.'], 404); // 204 No Content, or 404 Not Found
        }
            if (!$response->successful()) {
                return response()->json([
                    'status' => 0,
                    'msn' => 'Error al guardar el préstamo: la petición falló con un código ' . $response->status(),
                    'codigoError'=> $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $data,
                    'responseLog' => $response
                ]);
            }
            
            $loanResponse = $response->json();
            // dd($loanResponse);
            if (isset($loanResponse['ErrorMessage'])) {
                $errorResponse = $loanResponse['ErrorMessage'];
                $msn = 'Error al guardar el préstamo: ' . $errorResponse['msn'];
                $status = (int)$errorResponse['status'];
            } else {
                if ($loanResponse['status'] !== '1') {
                    $msn = 'Error al guardar el préstamo: ' . $loanResponse['msn']; //verificar aqui
                    $status = (int)$loanResponse['status'];
                } else {
                    if (strpos($loanResponse['msn'], 'guardado') !== false) {
                        $msn = 'El préstamo se guardó correctamente.';
                        $status = (int)$loanResponse['status'];
                    } else {
                        $msn = 'Error al guardar el préstamo: ' . $loanResponse['msn'];
                        $status = (int)$loanResponse['status'];
                    }
                }
            }
            
            
            return response()->json([
                'status' => $status,
                'msn' => $msn,
                'data' => $loanResponse
            ]);
            
    
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función canHaveLoan() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $data,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función canHaveLoan() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al guardar el prestamo.'];
        
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion saveLoanController() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $data,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion saveLoanController() al consumir el servicio s2credit', $error);

            if ($t instanceof \Illuminate\Http\Client\RequestException && $t->getCode() == 28) {
                Log::error("Error al conectarse al servicio de guardado de préstamos: " . $t->getMessage());

                return response()->json([
                    'status' => 0,
                    'msn' => 'Error al conectarse al servicio de guardado de préstamos: ' . $t->getMessage()
                ]);
            }
            Log::error("Error al guardar el préstamo: " . $t->getMessage());

            return response()->json([
                'status' => 0,
                'msn' => 'Error al guardar el préstamo: ' . $t->getMessage()
            ]);
        }
    }
    
    public function storeBeneficiary(Request $request)
    {
        $request->validate([
            'id_relationship' => 'required|string',
            'id_purchase' => 'required|string',
            'name' => 'required|string',
            'last_name' => 'required|string',
            'second_last_name' => 'required|string',
        ]);


        $beneficiaryData = [
            'id_relationship' => $request->input('id_relationship'),
            'id_purchase' => $request->input('id_purchase'),
            'name' => $request->input('name'),
            'last_name' => $request->input('last_name'),
            'second_last_name' => $request->input('second_last_name'),
        ];

        try {
            DB::enableQueryLog();
            $response = SaveLoan::saveBeneficiary($beneficiaryData);            
        } catch (\Throwable $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion storeBeneficiary() al intentar guardar el beneficiario',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion storeBeneficiary() al intentar guardar el beneficiario', $error);

            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el beneficiario: ' . $t->getMessage(),
            ], 400);
        } 

        return response()->json($response);
    }
    public function swapTransfer(Request $request)
    {
        try{

            DB::enableQueryLog(); // Habilitar el registro de consultas
            $numReference = Configuracion::obtenerValorPorParametro('numReference');
        }catch (\Throwable $t){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion swapTransfer() al intentar ejecutar obtenerValorPorParametro("numReference")',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile()
            ];    
            Log::error('Error en la funcion swapTransfer() al intentar ejecutar obtenerValorPorParametro("numReference")', $error);
        }        
        $rawTransferData  = $request->only([
            'sociedad',
            'transferId',
            'description',
            'account',
            'amount',
            'bank',
            'owner',
            'ownerPhone',
        ]);
        
        $rawTransferData['numReference'] = $numReference;

        $transferData = $rawTransferData;
        $transferData['amount'] = (int) $rawTransferData['amount'];
        // dd($transferData);
        try {
            DB::enableQueryLog();
            $response = SaveLoan::swapTransfer($transferData); 
        } catch (\Throwable $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion swapTransfer() al intentar ejecutar SaveLoan::swapTransfer($transferData)',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion swapTransfer() al intentar ejecutar SaveLoan::swapTransfer($transferData)', $error);
            
            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        }
        log::info('Respuesta de swapTransfer: ', $response);
        return response()->json($response);
    }
    public function getSwapTransfer(Request $request)
    {
               

        $rawTransferData  = $request->only([
            'sociedad',
            'transferId',
        ]);


        $transferData = $rawTransferData;
       
        $response = null;
        try {
            $response = SaveLoan::getSwapTransfer($transferData);            
        } catch (\Throwable $t) {

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getSwapTransfer() al intentar ejecutar SaveLoan::getSwapTransfer($transferData)',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion getSwapTransfer() al intentar ejecutar SaveLoan::getSwapTransfer($transferData)', $error);
            
            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [],
            ]);
        }

        return response()->json($response);
    }
    public function tokaApi(Request $request)
    {
        $rawTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Nombre',
            'Paterno',
            'Materno',
            'id',

        ]);

        $transferData = $rawTransferData;
        $response = null;
        try {

            $response = SaveLoan::tokaApi($transferData);            
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion tokaApi() al intentar ejecutar SaveLoan::tokaApi($transferData)',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion tokaApi() al intentar ejecutar SaveLoan::tokaApi($transferData)', $error);

            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        }
        
        return response()->json($response);
    }
    public function cambioEstado(Request $request)
    {
        $antesTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Estado',
        ]);

        $transferData = $antesTransferData;
        $response = null;
        try {
            $response = SaveLoan::cambioEstado($transferData);            
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion cambioEstado() al intentar ejecutar SaveLoan::cambioEstado($transferData)',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion cambioEstado() al intentar ejecutar SaveLoan::cambioEstado($transferData)', $error);

            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        }

        return response()->json($response);
    }
    public function tokaDispersiones(Request $request)
    {
                    

        $antesTransferData  = $request->only([
            'sociedad',
            'NumeroTarjeta',
            'Estado',
        ]);

        $transferData = $antesTransferData;
        $response = null;
        try {
            $response = SaveLoan::tokaDispersiones($transferData);            
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion tokaDispersiones() al intentar ejecutar SaveLoan::tokaDispersiones($transferData)',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $transferData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion tokaDispersiones() al intentar ejecutar SaveLoan::tokaDispersiones($transferData)', $error);
            
            return response()->json([
                'status' => 0,
                'message' => $t->getMessage(),
                'data' => [], 
            ]);
        }
        return response()->json($response);
    }
    public function store(Request $request)
    {
        $IDCliente = $request->input('IDCliente');
        $SecFile = "0";
        $NoCuenta = ""; 
        $Importe = $request->input('Importe');
        $Intereses = $request->input('Intereses'); //INTERESES
        // $DPVale=$request->input('NumFact');
        $inputValue = $request->input('NumFact');
        $DPVale = $inputValue; 
        if(is_numeric($inputValue)){
            // Si es numérico y su longitud es menor a 10, rellenar con ceros a la izquierda
            if(strlen((string) $inputValue) <= 10){
                $DPVale = str_pad($inputValue, 10, "0", STR_PAD_LEFT);
            } else {
                // Manejar el caso donde $inputValue es numérico pero tiene una longitud mayor a 10
                return response()->json(['error' => 'Número de factura no válido'], 400);
            }
        }
        // dd($DPVale);
        $Tipo = $request->input('Tipo', '1');
        $CodAlmacen = "001";
        $NumFact = $request->input('NumFact');
        $IDAsociado = $request->input('IDAsociado');
        if (isset(session("USERDATA")["Plazas"])) {
            $CodPlaza = session("USERDATA")["Plazas"];
        } elseif (isset(session("USERDATA")["CodPlaza"])) {
            $CodPlaza = session("USERDATA")["CodPlaza"];
        }
        $Oficina = session("USERDATA")["Oficina"];
        $AltaTarjeta = "";
        $NoIFE = $request->input('NoIFE');
        $Fecha = $request->input('Fecha');
        $fechaObj = DateTime::createFromFormat('Y-m-d', $Fecha);
        $fechaObj->setTime(15, 26, 30);
        $fechaObj->setTimezone(new DateTimeZone('UTC')); 
        $fechaObj->setTimestamp($fechaObj->getTimestamp() + 183000 / 1000); // Agregar 183,000 microsegundos
        // Obtener los milisegundos actuales con solo 3 dígitos
        $milliseconds = sprintf("%03d", fmod($fechaObj->format('u'), 1000000) / 1000);        // Combinar la fecha, la hora y los milisegundos en una cadena
        $fechaStr = $fechaObj->format('Y-m-d H:i:s') . '.' . $milliseconds;
        $FolioFIIntereses = "";
        $FolioFIMonto = $request->input('FolioFIMonto'); //DUDA
        $Usuario = session('Usuario');
        $Benef = '';
        $Banco = $request->input('Banco');
        $Celular = $request->input('Celular');
        $CompañiaCelular = "";
        $Clabe = $request->input('Clabe', '');
        $Transfer = "0";
        $NumeroTarjeta = $request->input('NumeroTarjeta', '');
        // dd($NumeroTarjeta, $Clabe);
        $ServicioId = $request->input('ServicioId');
        $Codigo = $request->input('Codigo');
        $NombreCliente = $request->input('NombreCliente');
        $Generado = "1";
        $now = microtime(true);
        $milliseconds = sprintf("%03d", fmod($now, 1) * 1000);
        $FechaDispersion = date("Y-m-d H:i:s", $now) . '.' . $milliseconds;
        $CodigoSeguridad = "";
        $RFC = $request->input('RFC','');
        $FechNac = $request->input('FechNac'); 
        $Dispersion = $request->input('Dispersion');
        $NoColaborador = session("Usuario");

        $Estatus = $request->input('Estatus');
        $Sociedad = $request->input('Sociedad');

        // dump($IDCliente, $SecFile, $NoCuenta, $Importe, $Intereses, $Tipo, $CodAlmacen, $NumFact,
        //     $IDAsociado, $CodPlaza, $Oficina, $AltaTarjeta, $NoIFE, $Fecha, $FolioFIIntereses,
        //     $FolioFIMonto, $Usuario, $Benef, $Banco, $Celular, $CompañiaCelular, $Clabe, $Transfer,
        //     $NumeroTarjeta, $ServicioId, $Codigo, $NombreCliente, $Generado, $FechaDispersion,
        //     $CodigoSeguridad, $RFC, $FechNac, $Dispersion, $NoColaborador, $Estatus, $Sociedad);

        try{
            DB::enableQueryLog(); // Habilitar el registro de consultas
            $result = SaveLoan::store(
            $IDCliente, $SecFile, $NoCuenta, $Importe, $Intereses, $DPVale, $Tipo, $CodAlmacen, $NumFact,
            $IDAsociado, $CodPlaza, $Oficina, $AltaTarjeta, $NoIFE, $fechaStr, $FolioFIIntereses,
            $FolioFIMonto, $Usuario, $Benef, $Banco, $Celular, $CompañiaCelular, $Clabe, $Transfer,
            $NumeroTarjeta, $ServicioId, $Codigo, $NombreCliente, $Generado, $FechaDispersion,
            $CodigoSeguridad, $RFC, $FechNac, $Dispersion, $NoColaborador, $Estatus, $Sociedad
        );
        }catch(\Throwable $t){

            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion store() al intentar ejecutar SaveLoan::store()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion store() al intentar ejecutar SaveLoan::store()', $error);
        }
        

        // Comprueba si la operación fue exitosa
        if ($result) {
            return response()->json(['message' => 'Datos guardados correctamente'], 200);
        } else {
            return response()->json(['message' => 'Error al guardar los datos'], 400);
        }
    }

    public function getFileContentsUsingCurl($localPath) {
        if (file_exists($localPath)) {
            return file_get_contents($localPath);
        }
    
        throw new \Exception("Archivo no encontrado: $localPath");
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la Funcion getFileContentsUsingCurl() Archivo no encontrado: $localPath'            
        ];    
        Log::error('Archivo no encontrado: $localPath', $error);
    }
    
    

    public function RegistrarTramite(Request $request)
    {
    $currentDate = new DateTime();
    $dateString = $currentDate->format('Y-m-d H:i:s');
    $formattedDate = $currentDate->format('Y-m-d H:i:s.u');
    $encrypt = $this->encryptService->getEncrypt();
    $nombreEncrypt = $encrypt[0]->Nombre; //CLAVE DE ENCRIPTACION

    // SP
    $CodPlaza = session("USERDATA")["CodPlaza"]; //LISTO
    $TiendaID = session("USERDATA")["TiendaID"]; // LISTO
    // $Caja = session("USERDATA")["Oficina"]; // Se obtiene del valor de ConfigAIO.Caja SE ELIMINÓ
    $FechaTienda = $formattedDate;
    $SociedadID = $request->input('sociedad'); //LISTO
    $TipoID = $request->input('Tipo'); // LISTO
    $DPVale = $request->input('NumFact'); //LISTO
    $DistribuidorID = $request->input('IDAsociado'); //LISTO
    $ClienteID = $request->input('IDCliente'); //LISTO
    $NoPlazo = $request->input('NoPlazo'); //LISTO
    $Importe = $request->input('importe'); //LISTO
    $Interes = $request->input('intereses'); //LISTO INTERESES
    $Seguro = $request->input('Seguro'); //Listo
    // $VigFechaInicio = $request->input('VigFechaInicio', "2022-12-20"); //REVISAR SE ELIMINA
    // $VigFechaFin = $request->input('VigFechaFin', "2022-12-20"); //REVISAR SE ELIMINA
    $MontoSeguro = $request->input('MontoSeguro', 'null');
        // if ($MontoSeguro === null) {
        //     $MontoSeguro = 0; // o cualquier valor predeterminado que desees usar
        // }
    $pagoQuincenal = $request->input('pagoQuincenal'); //LISTO
    $ServicioID =  $request->input('idServicio'); //LISTO
    $BancoID = $request->input('BancoID'); //LISTO
    $Banco = $request->input('Banco'); //LISTO
    /* INICIA PROCESO DODNE SE ENVIA Y SE ENCRIPTA LA TARJETA */
    $TarjetaClabeRecibe = $request->input('numeroTarjeta'); //LISTO
    // dd($TarjetaClabeRecibe);
    if ($ServicioID == 9 || $ServicioID == 15) {
        $TarjetaClabe = $TarjetaClabeRecibe;
    } else {
        $TarjetaClabeEncrypt  = $this->encryptService->Encrypt(0,$nombreEncrypt, $TarjetaClabeRecibe);
        $TarjetaClabeEnc = $TarjetaClabeEncrypt['Resultado'][0]['datos'][0]['data'];         
        $TarjetaClabe = base64_encode($TarjetaClabeEnc);
    }
    /* TERMINA PROCESO DODNE SE ENVIA Y SE ENCRIPTA LA TARJETA */

    // $EstatusDispToka = $request->input('EstatusDispToka'); // Establecer según el servicio seleccionado en el combo SE ELIMINA
    $EstatusDispersion =$request->input('estatus'); //LISTO
    // $Estatus = '1'; // SE ELIMINA

    /* INICIA VALIDACIÓN Y DATOS DEL NOMBRE */
    $NombreDistribuidor = $request->input('distCompleto'); //LISTO
    if ($NombreDistribuidor !== null) {
        $NombreDistribuidor = preg_replace('/null\s/', '', $NombreDistribuidor);
    } else {
        $NombreDistribuidor = "";
    }
    /* FINALIZA VALIDACIÓN Y DATOS DEL NOMBRE */
    $Firma= $request->input('firma'); //LISTO
    
    $NombreCliente = $request->input('NombreCliente'); //LISTO

    $IdentificacionID = $request->input('inputIdentificacionId'); //LISTO
    $Identificacion = $request->input('inputIdentificacion'); // LISTO
    $Celular = $request->input('inputCel'); //LISTO

    // $benefInput = $request->input('Benef'); //SE ELIMINA O ENVIAR NULL
    /* INICIA VALIDACIÓN DE BENEFICIARIO */
    // $percentageInput = $request->input('percentage'); SE ELIMINA
    
    // if (isset($benefInput) && isset($percentageInput)) {
    //     $Beneficiario = $benefInput . '-' . $percentageInput;
    // } elseif (isset($benefInput)) {
    //     $Beneficiario = $benefInput;
    // } elseif (isset($percentageInput)) {
    //     $Beneficiario = $percentageInput;
    // } else {
    //     $Beneficiario = '';
    // }
    /* FINALIZA VALIDACIÓN DE BENEFICIARIO */

    // $NoColaborador = session('NoColaborador');//SE ELIMINA
    // $FotoIdentiFrontral = $request->input('frontIneBase64Image'); SE ELIMINA
    // $FotoIdentiTrasera = $request->input('backIneBase64Image'); SE ELIMINA

       
        // $FotoIdentiFrontral = $request->input('frontIneBase64Image'); // Obtener en base64
        // $FotoIdentiTrasera = $request->input('backIneBase64Image');
        // FVale: Generar y modificar en documento PDF según si es numérico (físico) o electrónico
    $FVale = $request->input('globalBase64Image','');
        
        // Obtener en base64 o generar/modificar PDF
        // Añadiendo las variables faltantes
    $UsuarioID = session('IDuser');//LISTO
        // $ticketBinary = $request->input('Ticket'); SE ELIMINA
    // $polizaBinary = $request->input('Poliza',''); SE ELIMINA
    // $futuroABCBinary = $request->input('FuturoABC',''); SE ELIMINA
    // $Ticket = hash('sha3-256', $ticketBinary); SE ELIMINA
    // $Poliza = $polizaBinary !== '' ? hash('sha3-256', $polizaBinary) : ''; SE ELIMINA
    // $FuturoABC = $futuroABCBinary !== '' ? hash('sha3-256', $futuroABCBinary) : ''; SE ELIMINA
    $validSeguro = $request->input('validSeguro',null); //LISTO
    // dd($validSeguro);
    $enviarBucket = (int) $request->session()->get('enviarBucket', false); //LISTO
    $Compra= $request->input('compra',null); //LISTO
    // $Parentesco = $request->input('Parentesco'); SE ELIMINA
    $FechaInicio = $request->input('fechaInicio',null);

    if ($FechaInicio !== null) {
        // Elimina la parte de la cadena de tiempo después de GMT-0700
        $FechaInicio = preg_replace('/ \(.*\)$/', '', $FechaInicio);
    
        $FechaInicio = new DateTime($FechaInicio);
        
        $vigenciaSeguro = DB::table('Configuracion')
            ->where('Parametro', 'DiasVigenciaSeguro')
            ->value('Valor');
    
        $loanTerm = $request->input('loanTerm', null);
    
        if ($loanTerm !== null) {
            $FechaFin = clone $FechaInicio;
            $FechaFin->add(new \DateInterval('P' . ($vigenciaSeguro * $loanTerm) . 'D'));
        } else {
            $FechaFin = null;
        }
    } else {
        $FechaFin = null;
    }
    $FechaInicioString = $FechaInicio !== null ? $FechaInicio->format('Y-m-d H:i:s') : null;
    $FechaFinString = $FechaFin !== null ? $FechaFin->format('Y-m-d H:i:s') : null;
    
    
    $NoPoliza=$request->input('seguroSeleccionado',null);
    $RFC=$request->input('rfc',null);
    $Genero=$request->input('genero',null);
    $Correo=$request->input('email',null);
    // log::info('Session',$request->session()->all());
    // Log::info('Datos enviados: ', ['enviarBucket' => $enviarBucket]);
    // Asegurarse de que los tipos de datos coincidan con la lista proporcionada
        $Importe = (float) $Importe;
        $Interes = (float) $Interes;
        $Seguro = (float) $Seguro;

        // $EstatusDispToka = (bool) $EstatusDispToka; SE ELIMINA

        // $Estatus = (bool) $Estatus; // SE ELIMINA
// dd($CodPlaza, $TiendaID, $SociedadID, $TipoID, $DPVale, $DistribuidorID, $ClienteID, 
// $NoPlazo, $Importe, $Interes, $pagoQuincenal, $MontoSeguro, $Seguro, $ServicioID, $BancoID, 
// $Banco, $TarjetaClabe, $EstatusDispersion, $NombreDistribuidor, $Firma, $NombreCliente, $IdentificacionID, 
// $Identificacion, $Celular, $UsuarioID, $validSeguro, $enviarBucket, $Compra, $FechaInicio, $FechaFin, $NoPoliza, $RFC, $Genero, $Correo);
        try {
            DB::enableQueryLog(); // Habilitar el registro de consultas
            $result = SaveLoan::registrarTramite(
                $CodPlaza, //ya
                $TiendaID, //ya
                // $Caja, //se elimina
                $SociedadID,//ya
                $TipoID, // ya
                $DPVale, //ya
                $DistribuidorID, //ya
                $ClienteID, // ya
                $NoPlazo, // ya
                $Importe, // ya
                $Interes, // ya
                $pagoQuincenal, //se Agrega
                $MontoSeguro, //se Agrega
                $Seguro, 
                // $VigFechaInicio, //se elimina
                // $VigFechaFin, // se elimina
                $ServicioID, //ya
                $BancoID, //ya
                $Banco, //ya 
                $TarjetaClabe, //ya
                // $EstatusDispToka, //se elimina
                $EstatusDispersion, //ya
                $NombreDistribuidor, //ya
                $Firma, //se agrega
                $NombreCliente, // ya
                $IdentificacionID, // ya
                $Identificacion, // ya
                $Celular, // ya
                // $Beneficiario, //ya enviar null
                $UsuarioID, // ya
                // $Estatus, // se elimina
                // $FotoIdentiFrontral, // se elimina
                // $FotoIdentiTrasera, // se elimina
                // $FVale, // se elimina
                // $Ticket, // se elimina
                // $Poliza, // se elimina
                // $FuturoABC, // se elimina
                $validSeguro, // 
                $enviarBucket, // ya+
                $Compra, // se agrega id.purchase save
                
                // $Parentesco, // se agrega se manda null
                $FechaInicioString, // se agrega
                $FechaFinString, // se agrega
                $NoPoliza, // se agrega
                $RFC, // se agrega
                $Genero, // se ag rega
                $Correo // se agrega
            );
                  

            if ($result !== false && $result !== null) {
                return response()->json(['message' => $result], 200);
            } else {
                return response()->json(['message' => $result], 400);
            }
            
        } catch (\Throwable $t) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion RegistrarTramite() al intentar ejecutar SaveLoan::registrarTramite()',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];    
            Log::error('Error en la funcion RegistrarTramite() al intentar ejecutar SaveLoan::registrarTramite()', $error);
            //Log::error('Error en RegistrarTramite: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' del archivo ' . $e->getFile());
            return response()->json(['message' => 'Error al guardar los datos, por favor verifica el log para más información'], 400);
        }

    }
    public function states()
    {
        $requestData = [
            'states' => []
        ];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'states' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion states() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion states() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function paises()
    {
        $requestData = [
            'paises' => []
        ];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'paises' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion paises() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion paises() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function ocupacionProfesion()
    {
        $requestData = [
            'ocupacion_profesion' => []
        ];
        $response = null;
        try{
            
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'ocupacion_profesion' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion ocupacionProfesion() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion ocupacionProfesion() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function destinoRecursos()
    {
        $requestData = [
            'destino_recursos' => []
        ];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'destino_recursos' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion destinoRecursos() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion destinoRecursos() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function estados()
    {
        $requestData = [
            'estados' => []
        ];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'estados' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion estados() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion estados() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function genders()
    {
        $requestData = ['genders' => []];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'genders' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion genders() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion genders() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function maritalStatus()
    {
        $requestData = ['marital-status' => []];
        $response = null;
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'marital-status' => [] 
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion maritalStatus() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion maritalStatus() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
    public function validateZipCode(Request $request)
    {
        $requestData = [
            'sepomex' => [
                'zipcode' => $request->input('zipcode'),
            ],
        ];
        $response = null;
        $zipCode = $request->input('zipcode');
        try{
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'sepomex' => [
            //         'zipcode' => $zipCode,
            //     ],
            // ]);
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $requestData);
        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion validateZipCode() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response
            ];    
            Log::error('Error en la funcion validateZipCode() al intentar consumir el servicio s2credit', $error);
        }        

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['status' => 'error', 'message' => 'Error al validar el código postal'], 500);
    }
    public function saveCustomer(Request $request)
    {
        $customerData = [
            "id_customer" => $request->input('id_customer', '0'),
            "name" => $request->input('name'),
            "middle_name" => $request->input('middle_name') === null ? '' : $request->input('middle_name'),
            "last_name" => $request->input('last_name'),
            "second_last_name" => $request->input('second_last_name'),
            "birthdate" => $request->input('birthdate'),
            "marital_status" => $request->input('marital_status'),
            "gender" => $request->input('gender'),
            "email" => $request->input('email', ''),
            "rfc" => $request->input('rfc'),
            "curp" => '',
            "id_identification" => '',
            "identification_value" => '',
            "id_profession" => $request->input('id_profession'),
            "id_birth_country" => '1',
            "id_birth_state" => $request->input('id_birth_state'),
            "id_nationality" => '',
            "id_residence_country" => '',
            "id_migratory_type" => '',
            "id_identification_authority" => '',
            "fiel" => '',
            "migratory_number" => '',
            "addressCollection" => [
                [
                    "street" => $request->input('street'),
                    "houseNumber" => $request->input('houseNumber'),
                    "apartmentNumber" => $request->input('apartmentNumber', ''),
                    "zipcode" => $request->input('zipcode'),
                    "state" => $request->input('state'),
                    "city" => $request->input('city'),
                    "settlement" => $request->input('settlement'),
                    "neighborhood" => $request->input('neighborhood'),
                ],
            ],
            "phoneNumberCollection" => [
                [
                    "number" => $request->input('phone_number'),
                    "type" => 2,
                ],
            ],
            "Campos_PLD" => [
                "id_profesion" => $request->input('id_profession'),
                "id_nacionalidad" => $request->input('id_nationality'),
                "id_estado_nacimiento" => $request->input('id_birth_state'),
                "id_pais_nacimiento" => $request->input('id_pais_nacimiento'),
                "id_destino_recurso" => $request->input('id_destiny_resource'),
            ],
        ];
        // Log::info('Datos del cliente:', $customerData);
        $response = null;
        $data = [
            'save-customer' => $customerData,
        ];
        try{
            $response = Http::post($this->url_broker . $this->path_pos_s2credit, $data);

        }catch(\Throwable $t){
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion saveCustomer() al intentar consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'dpVale' => $request->input('id_customer', '0'), 
                'requestLog' => $data,
                'responseLog' => $response ? $response : "Sin respuesta" 
            ];    
            Log::error('Error en la funcion saveCustomer() al intentar consumir el servicio s2credit', $error);
            return response()->json(['status' => 'error', 'message' => 'Error al comunicarse con el servicio externo.']);

        }    

        // Verificar si la solicitud falló
        // if ($response->failed()) {
        //     return response()->json(['status' => 'error', 'message' => 'Ocurrió un error al guardar el cliente, favor de verificar']);
        // }

        $responseData = $response->json();

        // Verificar si la respuesta tiene un error
        if (isset($responseData['ErrorMessage']['status']) && $responseData['ErrorMessage']['status'] == 0) {
            $errorMessage = $responseData['ErrorMessage']['msn'];
        
            // Verificar si el mensaje es "Customer already exists" para traducirlo
            if ($errorMessage === 'Customer already exists') {
                $errorMessage = 'El cliente ya existe';
            }
        
            return response()->json(['status' => 'error', 'message' => "Error al guardar el cliente. Detalle: {$errorMessage}"], 400);
        }
        

        // Verificar si la respuesta tiene éxito y devuelve el mensaje correspondiente
        if (isset($responseData['status']) && $responseData['status'] == 1 && isset($responseData['customer'])) {
            $customerID = str_pad($responseData['customer']['id_customer'], 10, "0", STR_PAD_LEFT);
            
            if ($request->input('id_customer') == '0') {
                // Cliente registrado correctamente
                return response()->json(['status' => 'success', 'message' => "Cliente registrado correctamente. Folio: {$customerID}", 'customer' => $responseData['customer']]);
            } else {
                // Cliente modificado correctamente
                return response()->json(['status' => 'success', 'message' => "Cliente modificado correctamente. Folio: {$customerID}", 'customer' => $responseData['customer']]);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Error desconocido al guardar el cliente'], 500);
    }

    public function getPlantilla(Request $request){
        try{
            DB::enableQueryLog();
            $valor = $request->valor; 
            $plantilla = SaveLoan::getPlantilla($valor);
        } catch(\Exception $e){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error=[
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getPlantilla()',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];
            Log::error('Error en la función getPlantilla()', $error);
    
            if ($e->getMessage() == 'No se encontraron plantillas.') {
                return redirect('home')->withErrors(['No se encontraron plantillas. Contacte al administrador.']);
            } else {
                return redirect('home')->withErrors(['Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.']);
            }
        }
        return $plantilla;
    }
    
    public function getConfPlantilla(Request $request)
    {
        try {
            DB::enableQueryLog();
            $parametro = $request->parametro; 
            $valor = SaveLoan::getConfPlantilla($parametro); 
    
            return response()->json([
                'status' => 'success',
                'data' => $valor
            ]);
    
        } catch(\Exception $e) {
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta

            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getConfPlantilla()',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];
            Log::error('Error en la función getConfPlantilla()', $error);
    
            $errorMsg = '';
            if ($e->getMessage() == 'No se encontraron plantillas.') {
                $errorMsg = 'No se encontraron plantillas. Contacte al administrador.';
            } else {
                $errorMsg = 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.';
            }
    
            return response()->json([
                'status' => 'error',
                'message' => $errorMsg
            ]);
        }
    }

    public function getUrlBeneficiario() {
        try{
            DB::enableQueryLog();
            $url=ConfigService::obtenerUrlBeneficiario();
            return response()->json(['url' => $url]);
        }catch(\Exception $e){
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error=[
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la función getUrlBeneficiario()',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestLog,
                'responseLog' => $bindings
            ];
            Log::error('Error en la función getUrlBeneficiario()', $error);
            return response()->json(['url' => '']);
        }
    }

    public function validarTarjeta(Request $request)
{
    $url = ConfigService::obtenerValorPorParametro('url_validar_tarjeta');
    $data = [
        'NoTarjeta' => $request->input('tarjeta')
    ];
    $response = null;
    try{
        $response = Http::post($url, $data);
    //    $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
            if(empty($response)){
                return ['error' => 'No se encontró información en el servicio validarTarjeta. Contacte al administrador.'];
            }
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función validarTarjeta() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función getBranches() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al buscar plazas, favor de volver a intentar.'];

    }catch(\Throwable $t){
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion saveCustomer() al intentar consumir el servicio s2credit',                
            'codigoError'=> $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $data,
            'responseLog' => $response ? $response : "Sin respuesta" 
        ];    
        Log::error('Error en la funcion validarTarjeta() al intentar consumir el servicio s2credit', $error);
        return response()->json(['status' => 'error', 'message' => 'Error al comunicarse con el servicio externo.']);

    }    


    if ($response->successful()) {
        return $response->json();
    }

    return response()->json(['status' => 'error', 'message' => 'Error en la funcion validarTarjeta() al intentar consumir el servicio s2credit'], 500);
}

public function cancelarTramite(Request $request)
{
    $originalUserData = $request->session()->get('USERDATA');
    if ($originalUserData) {
        $request->session()->put('Usuario', $originalUserData['Usuario']);
    }

    return response()->json(['success' => true]);
}

}
