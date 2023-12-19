<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Configuracion;
use App\Models\SaveLoan;
use App\Services\ConfigService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;

use App\Services\SessionService;
use App\Services\EncryptService;
use Illuminate\Support\Facades\Validator;

use Aws\S3\S3Client;
use Aws\S3\PresignedUrl;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class ReporteFinancieroController extends Controller
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
        //Titulo de la vista
        $title = 'Reporte Financiero';

        $sessionLifetime = $this->sessionService->getSessionLifetime($this->request);
        
        $RoleID = session("RoleID");
        $CodPlaza = '';
        
        if ($RoleID == '6') {
            $CodPlaza = session("CodPlaza");
        }else{
            $CodPlaza = session("Plazas");
        }

        //Se obtiene el catalogo Plazas
        $catalogoPlazas = $this->getCatalogoPlazas();
                // dd($catalogoPlazas);
        //Se obtiene el catalogo Tiendas
        $requestTiendas = new Request();
        $requestTiendas->merge(['CodPlaza' => null]);        
        $catalogoTiendas = $this->getCatalogoTiendas($requestTiendas);

        $plazasUsuario = [];
        $tiendasUsuario = [];
        $ultimosPrestamos =[];
        
        if (in_array($RoleID,['2','3'])) {//Roles 2 y 3 Acceso a todas las plazas
            
            $plazasUsuario = $catalogoPlazas;
            $tiendasUsuario = $catalogoTiendas;

            //Se obtiene los ultimos 31 dias de prestamo
            $requestPrestamos = new Request();
            $requestPrestamos->merge(['FechaInicio' => date('Y-m-d H:i:s', strtotime('-31 days')), 'FechaFin' => date('Y-m-d H:i:s')]);
            $responsePrestamos = $this->obtenerTramites($requestPrestamos);
            // dd($responsePrestamos);
            if ($responsePrestamos != false) {
                $ultimosPrestamos = $responsePrestamos->original;
            }else{
                $ultimosPrestamos = $responsePrestamos;
            }                      
                        
        }else if(in_array($RoleID,['4','5'])){//Roles 4 y 5 Acceso solo a plazas asignadas
            
            $arrayPlazas = explode(",", $CodPlaza);

            foreach ($catalogoPlazas as $plaza) {                
                if (in_array($plaza->CodPlaza, $arrayPlazas)) {
                    $plazasUsuario[] = [$plaza->CodPlaza, $plaza->Plaza];
                }
            }

            //Se obtiene los ultimos 31 dias de prestamo
            $requestPrestamos = new Request();
            $requestPrestamos->merge(['FechaInicio' => date('Y-m-d', strtotime('-31 days')), 'FechaFin' => date('Y-m-d')]);
            // dd($requestPrestamos);
            $responsePrestamos = $this->obtenerTramites($requestPrestamos);
            // dd($responsePrestamos);
            if ($responsePrestamos != false) {
                // dd("me ejecuto primeros");
                foreach ($responsePrestamos->original as $prestamo) {                
                    if (in_array($prestamo->CodPlaza, $arrayPlazas)) {
                        $ultimosPrestamos[] = $prestamo;
                    }
                }
            }else{
                // dd("me ejecuto aca");
                $ultimosPrestamos = $responsePrestamos;
            }                        
            
        }else if(in_array($RoleID,['6'])){//Rol 6 Acceso solo a su plaza asignada
            
            $TiendaID = session("TiendaID");

            foreach ($catalogoTiendas->original as $tienda) {                
                if ($tienda->TiendaID == $TiendaID) {
                    $tiendasUsuario[] = [$tienda->TiendaID, $tienda->Tienda];                    
                    $plazasUsuario[] = [$tienda->CodPlaza, $tienda->Plaza];
                }
            }
            $CodPlaza = $plazasUsuario[0][0];
            // dd($CodPlaza);
            // dd($tienda);
            //Se obtiene los ultimos 31 dias de prestamo
            $requestPrestamos = new Request();
            $requestPrestamos->merge(['CodPlaza' => $CodPlaza, 'TiendaID' => $TiendaID, 'FechaInicio' => date('Y-m-d', strtotime('-31 days')), 'FechaFin' => date('Y-m-d')]);
            // dd($requestPrestamos);
            // echo '<pre>';
            // var_dump($requestPrestamos);
            // echo '</pre>';
            $responsePrestamos = $this->obtenerTramites($requestPrestamos);

            if ($responsePrestamos != false) {
                $ultimosPrestamos = $responsePrestamos->original;
            }else{
                $ultimosPrestamos = $responsePrestamos;
            }            
        }

        //Se obtiene el catalogo Tipos
        $catalogoTipos = $this->getCatalogoTipos();

        return view('home.aplicaciones.reporteFinanciero.index',
                    compact('title', 'plazasUsuario', 'tiendasUsuario', 'catalogoTipos',
                    'RoleID', 'CodPlaza','ultimosPrestamos', 'sessionLifetime')
                    );

    }

    public function getCatalogoPlazas(){
        try {
            DB::enableQueryLog();   
            $catalogoPlazas = Configuracion::getCatalogoPlazas();
        }catch (\Throwable $t){            
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
        
        $plazasUsuario = [];
        
        if ($catalogoPlazas) {
            
            return $catalogoPlazas;

        }else{
            return $plazasUsuario;
        }
    }

    public function getCatalogoTipos(){
        try {
            DB::enableQueryLog();   
            $catalogoTipos = Configuracion::getCatalogoTipos();
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoTipos().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoTipos()', $error);                
            return false;
        }
        
        return $catalogoTipos;        
    }

    public function getCatalogoTiendas(Request $request){
        
        $CodPlaza = $request->input('CodPlaza');
        // dd($CodPlaza);
        try {
            DB::enableQueryLog();
            $catalogoTiendas = Configuracion::getCatalogoTiendas($CodPlaza);            
        }catch (\Throwable $t){            
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
       
        return response()->json($catalogoTiendas);        
    }

    public function buscarDistribuidor(Request $request){
        
        $ValorBusqueda = $request->input('ValorBusqueda');

        try {
            DB::enableQueryLog();
            $distribuidores = Configuracion::BuscarDistribuidor($ValorBusqueda);
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion buscarDistribuidor().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion buscarDistribuidor()', $error);
            return false;
        }
        
        return response()->json($distribuidores);        
    }

    public function buscarCliente(Request $request){
        
        $ValorBusqueda = $request->input('ValorBusqueda');

        try {
            DB::enableQueryLog();
            $clientes = Configuracion::BuscarCliente($ValorBusqueda);
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion buscarCliente().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion buscarCliente()', $error);
            return false;
        }
        
        return response()->json($clientes);        
    }

    public function obtenerTramites(Request $request){
        
        $DPVale = $request->input('DPVale');
        $FechaInicio = $request->input('FechaInicio');
        $FechaFin = $request->input('FechaFin');
        $CodPlaza = $request->input('CodPlaza');
        $TiendaID = $request->input('TiendaID');
        $TipoID = $request->input('TipoID');
        $DistribuidorID = $request->input('DistribuidorID');
        $ClienteID = $request->input('ClienteID');
        $Notas = $request->input('Notas');
        // dd($FechaInicio, $FechaFin);
        try {
            DB::enableQueryLog();
            // dd($DPVale, $FechaInicio, $FechaFin, $CodPlaza, $TiendaID, $TipoID, $DistribuidorID, $ClienteID, $Notas);
            $tramites = Configuracion::ObtenerTramites($DPVale, $FechaInicio, $FechaFin, $CodPlaza, 
                                                    $TiendaID, $TipoID, $DistribuidorID,
                                                    $ClienteID, $Notas);
                      //esto se usa para imprimir consulta                              
        // $query = DB::getQueryLog();
        // $lastQuery = end($query);
        // dd($lastQuery);

        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion obtenerTramites().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion obtenerTramites()', $error);
            return false;
        }

        return response()->json($tramites);                
    }

    public function actualizarRevision(Request $request){
        
        $PrestamoID = (int) $request->input('dataPrestamoID');
        $RoleID = (int) session("RoleID");
        $chk = $request->input('chk');
        $UsuarioID = (int) session("UsuarioID");

        if ($RoleID == '4' || $RoleID == '5') {
            try {
                DB::enableQueryLog();
                $response = Configuracion::ActualizarRevision($PrestamoID, $RoleID, $chk, $UsuarioID);            
            }catch (\Throwable $t){            
                $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion actualizarRevision().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion actualizarRevision()', $error);
            }

            return response()->json($response); 

        } else{
            return false;
        }
    }

    public function getNotas(Request $request){
        $PrestamoID = $request->input('PrestamoID');

        try {
            DB::enableQueryLog();
            $notas = Configuracion::GetNotas($PrestamoID);            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getNotas().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getNotas()', $error);
            return false;
        }

        return response()->json($notas);        
    }

    public function agregarNota(Request $request){
        
        $PrestamoID = $request->input('PrestamoID');
        $RoleID = session("RoleID");
        $UsuarioID = session("UsuarioID");
        $Nota = $request->input('InputNotaModal');
        $NotaModal = '1'; //1 para indicar que la nota se esta agregando desde el modal        

        try {
            DB::enableQueryLog();
            $response = Configuracion::AgregarNota($PrestamoID, $RoleID, $UsuarioID, $Nota, $NotaModal);
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion agregarNota().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion agregarNota()', $error);
            return false;
        }

        return response()->json($response);
    }

    public function obtenerValorPorParametro($parametro){
                
        try {
            DB::enableQueryLog();
            $response = Configuracion::obtenerValorPorParametro($parametro);            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion obtenerValorPorParametro().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion obtenerValorPorParametro()', $error);
            return false;
        }

        return $response;
    }


    public function getCatalogoDocumentos(){
        try {
            DB::enableQueryLog();
            $catalogoDocumentos = Configuracion::GetCatalogoDocumentos();            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getCatalogoDocumentos().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getCatalogoDocumentos()', $error);
            return false;
        }

        return $catalogoDocumentos;
    }

    public function getPlantillas($Fecha, $ID_CatalogoDocumentos){
        
        try {
            DB::enableQueryLog();
            $plantillas = Configuracion::GetPlantillas($Fecha, $ID_CatalogoDocumentos);            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getPlantillas().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getPlantillas()', $error);
            return false;
        }

        return $plantillas;
    }

    public function getSeguro($DPVale){
        
        try {
            DB::enableQueryLog();
            $response = Configuracion::GetSeguro($DPVale);            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getSeguro().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getSeguro()', $error);
            return false;
        }

        return $response;
    }


    public function getDocumentosS3(Request $request){        
        
        $rowDataArray = $request->input('rowDataArray');

        if (in_array($rowDataArray[0]['TipoID'], ['1', '3'])) {//Se valida si el tipo de tramite es Vale Financiero o Revolvente
            
            //Se valida si el vale es numerico o alfanumerico
            $valeFisico;
	        $valeElectronico;
            if (ctype_alnum($rowDataArray[0]['DPVale'])) {
                // dd('Vale Electronico');
                if (ctype_digit($rowDataArray[0]['DPVale'])) {//Se valida si el vale es Numerico
                    $valeFisico = true;
                    $valeElectronico = false;
                } else {//Se valida si el vale es AlfaNumerico
                    $valeFisico = false;
                    $valeElectronico = true;                    
                }
            }
            
            if ($valeFisico) { //Se valida si el vale es fisico
                if ($rowDataArray[0]['SociedadID'] == '1') {//CDPT
                    // dd('Vale Fisico CDPT');
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentosValeCDPT';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeCDPTSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);

                    if (!$responseParam) {
                        return false;
                    }
    
                    //$Fecha = null; //borrar esta linea, solo fue para probar
                    $Fecha = $rowDataArray[0]['Fecha']; 
                    $ID_CatalogoDocumentos = $responseParam;
    
                    $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);
                    
                    if (!$responsePlantillas || isset($responsePlantillas[0]->status)) {
                        return false;
                    }

                    //se obtiene el prefijo para la ruta                                                
                    $parametro = 'url_valeFinanciero';
    
                    $responsePrefijo = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responsePrefijo) {
                        return false;
                    }    
                        
                    $Prefijo = $responsePrefijo;
                    $Fecha = $rowDataArray[0]['Fecha'];
                    $Vale = $DPVale;
                    $IdentificacionID = $rowDataArray[0]['IdentificacionID'];
                    
                    //Se seapara la Fecha
                                    
                    $dateComponents = date_parse($Fecha);
    
                    $año = $dateComponents["year"];
                    $mes = $dateComponents["month"];
                    $mesNombre = $this->getNombreMes($mes);                
                    $dia = $dateComponents["day"];
                    
                    $mesForm = sprintf("%02d", $dateComponents["month"]);
                    $diaForm = sprintf("%02d", $dateComponents["day"]);
                    $fechaCompleta = $año.$mesForm.$diaForm;
                    
                    $arrayDocumentos = [];

                    for ($i = 0; $i < count($responsePlantillas); $i++) {
                        $Codigo = $responsePlantillas[$i]->Codigo;
                        $NombreDocumento = $responsePlantillas[$i]->NombreDocumento;
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/'.$NombreDocumento.'/'.$Codigo.$Vale;
    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
                                                                                
                    if (in_array($IdentificacionID, ['8', '2'])) {
                        $rutaCompleta1 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-1';                    
                        $arrayDocumentos[] = $rutaCompleta1;
                        
                        $rutaCompleta2 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-2';
                        $arrayDocumentos[] = $rutaCompleta2;
                    }else if($IdentificacionID == '3'){
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale;                    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
                    // dd($arrayDocumentos);
                    //Armado de ruta para la imagen del vale físico
                    $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/VALE/VI1'.$Vale;
                    $arrayDocumentos[] = $rutaCompleta;
                    // dd($arrayDocumentos);
                    if (count($rowDataArray) > 1) {                
                        return $arrayDocumentos;
                    } else {                                
                        
                        $req = new Request();
                        $req->merge(['arrayDocumentos' => $arrayDocumentos, 'Vale' => $Vale, 'IdentificacionID' => $IdentificacionID]);
                        
                        $responseDocumentos = $this->getDocumentosAwsS3($req);

                        return response()->json($responseDocumentos);
                    }

                } else if ($rowDataArray[0]['SociedadID'] == '2') {//SOFOM
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {                        
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentoValeSOFOM';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentoValeSOFOMSeguro';
                        }
                    }
                    // dd($parametro);
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
                    // dd($responseParam);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    //$Fecha = null; //borrar esta linea, solo fue para probar
                    $Fecha = $rowDataArray[0]['Fecha']; 
                    $ID_CatalogoDocumentos = $responseParam;
    
                    $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);
                    
                    if (!$responsePlantillas) {
                        return false;
                    }
    
                    //se obtiene el prefijo para la ruta                                                
                    $parametro = 'url_valeFinanciero';
    
                    $responsePrefijo = $this->obtenerValorPorParametro($parametro);
                    // dd($responsePrefijo);
                    if (!$responsePrefijo) {
                        return false;
                    }    
                        
                    $Prefijo = $responsePrefijo;
                    $Fecha = $rowDataArray[0]['Fecha'];
                    $Vale = $DPVale;
                    $IdentificacionID = $rowDataArray[0]['IdentificacionID'];
                    
                    //Se seapara la Fecha
                                    
                    $dateComponents = date_parse($Fecha);
    
                    $año = $dateComponents["year"];
                    $mes = $dateComponents["month"];
                    $mesNombre = $this->getNombreMes($mes);                
                    $dia = $dateComponents["day"];
                    
                    $mesForm = sprintf("%02d", $dateComponents["month"]);
                    $diaForm = sprintf("%02d", $dateComponents["day"]);
                    $fechaCompleta = $año.$mesForm.$diaForm;
                    
                    $arrayDocumentos = [];
    
                    for ($i = 0; $i < count($responsePlantillas); $i++) {
                        $Codigo = $responsePlantillas[$i]->Codigo;
                        $NombreDocumento = $responsePlantillas[$i]->NombreDocumento;
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/'.$NombreDocumento.'/'.$Codigo.$Vale;
    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
                    //  dd($arrayDocumentos);                                                           
                    if (in_array($IdentificacionID, ['8', '2'])) {
                        $rutaCompleta1 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-1';                    
                        $arrayDocumentos[] = $rutaCompleta1;
                        
                        $rutaCompleta2 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-2';
                        $arrayDocumentos[] = $rutaCompleta2;
                    }else if($IdentificacionID == '3'){
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale;                    
                        $arrayDocumentos[] = $rutaCompleta;
                    }

                    //Armado de ruta para la imagen del vale físico
                    $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/VALE/VI1'.$Vale;
                    $arrayDocumentos[] = $rutaCompleta;
                            // dd($arrayDocumentos);
                    if (count($rowDataArray) > 1) { //Se valida si tiene el valor verificador de la funcion validadorDocumentos    
                        // dd($arrayDocumentos);           
                        return $arrayDocumentos;
                    } else {                                
                        
                        $req = new Request();
                        $req->merge(['arrayDocumentos' => $arrayDocumentos, 'Vale' => $Vale, 'IdentificacionID' => $IdentificacionID]);
                        
                        $responseDocumentos = $this->getDocumentosAwsS3($req);
                        // dd($responseDocumentos);
                        return response()->json($responseDocumentos);
                    }
                    
                }
            } else if ($valeElectronico){ //Se valida si es vale electrónico (alfanumerico)
                if ($rowDataArray[0]['SociedadID'] == '1') {//CDPT
                
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentosValeCDPTElectronico';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeCDPTElectronicoSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    //$Fecha = null; //borrar esta linea, solo fue para probar
                    $Fecha = $rowDataArray[0]['Fecha']; 
                    $ID_CatalogoDocumentos = $responseParam;
    
                    $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);
                    
                    if (!$responsePlantillas) {
                        return false;
                    }
    
                    //se obtiene el prefijo para la ruta                                                
                    $parametro = 'url_valeFinanciero';
    
                    $responsePrefijo = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responsePrefijo) {
                        return false;
                    }
    
                    
                    $Prefijo = $responsePrefijo;
                    $Fecha = $rowDataArray[0]['Fecha'];
                    $Vale = $DPVale;
                    $IdentificacionID = $rowDataArray[0]['IdentificacionID'];
                    
                    //Se seapara la Fecha
                                    
                    $dateComponents = date_parse($Fecha);
    
                    $año = $dateComponents["year"];
                    $mes = $dateComponents["month"];
                    $mesNombre = $this->getNombreMes($mes);                
                    $dia = $dateComponents["day"];
                    
                    $mesForm = sprintf("%02d", $dateComponents["month"]);
                    $diaForm = sprintf("%02d", $dateComponents["day"]);
                    $fechaCompleta = $año.$mesForm.$diaForm;
                    
                    $arrayDocumentos = [];
    
                    for ($i = 0; $i < count($responsePlantillas); $i++) {
                        $Codigo = $responsePlantillas[$i]->Codigo;
                        $NombreDocumento = $responsePlantillas[$i]->NombreDocumento;
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/'.$NombreDocumento.'/'.$Codigo.$Vale;
    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
                                                                                
                    if (in_array($IdentificacionID, ['8', '2'])) {
                        $rutaCompleta1 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-1';                    
                        $arrayDocumentos[] = $rutaCompleta1;
                        
                        $rutaCompleta2 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-2';
                        $arrayDocumentos[] = $rutaCompleta2;
                    }else if($IdentificacionID == '3'){
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale;                    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
        
                    if (count($rowDataArray) > 1) {                
                        return $arrayDocumentos;
                    } else {

                        $req = new Request();
                        $req->merge(['arrayDocumentos' => $arrayDocumentos, 'Vale' => $Vale, 'IdentificacionID' => $IdentificacionID]);

                        $responseDocumentos = $this->getDocumentosAwsS3($req);
                        return response()->json($responseDocumentos);
                    }
    
                } else if ($rowDataArray[0]['SociedadID'] == '2') {//SOFOM
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentoValeSOFOMElectronico';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeSOFOMElectronicoSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    //$Fecha = null; //borrar esta linea, solo fue para probar
                    $Fecha = $rowDataArray[0]['Fecha']; 
                    $ID_CatalogoDocumentos = $responseParam;
    
                    $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);
                    
                    if (!$responsePlantillas) {
                        return false;
                    }
    
                    //se obtiene el prefijo para la ruta                                                
                    $parametro = 'url_valeFinanciero';
    
                    $responsePrefijo = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responsePrefijo) {
                        return false;
                    }
    
    
                    $Prefijo = $responsePrefijo;
                    $Fecha = $rowDataArray[0]['Fecha'];
                    $Vale = $DPVale;
                    $IdentificacionID = $rowDataArray[0]['IdentificacionID'];
                    
                    //Se seapara la Fecha
                                    
                    $dateComponents = date_parse($Fecha);
    
                    $año = $dateComponents["year"];
                    $mes = $dateComponents["month"];
                    $mesNombre = $this->getNombreMes($mes);                
                    $dia = $dateComponents["day"];
                    
                    $mesForm = sprintf("%02d", $dateComponents["month"]);
                    $diaForm = sprintf("%02d", $dateComponents["day"]);
                    $fechaCompleta = $año.$mesForm.$diaForm;
                    
                    $arrayDocumentos = [];
    
                    for ($i = 0; $i < count($responsePlantillas); $i++) {
                        $Codigo = $responsePlantillas[$i]->Codigo;
                        $NombreDocumento = $responsePlantillas[$i]->NombreDocumento;
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/'.$NombreDocumento.'/'.$Codigo.$Vale;
    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
                                                                                
                    if (in_array($IdentificacionID, ['8', '2'])) {
                        $rutaCompleta1 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-1';                    
                        $arrayDocumentos[] = $rutaCompleta1;
                        
                        $rutaCompleta2 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-2';
                        $arrayDocumentos[] = $rutaCompleta2;
                    }else if($IdentificacionID == '3'){
                        $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale;                    
                        $arrayDocumentos[] = $rutaCompleta;
                    }
        
                    if (count($rowDataArray) > 1) {                
                        return $arrayDocumentos;
                    } else {             

                        $req = new Request();
                        $req->merge(['arrayDocumentos' => $arrayDocumentos, 'Vale' => $Vale, 'IdentificacionID' => $IdentificacionID]);

                        $responseDocumentos = $this->getDocumentosAwsS3($req);
                        return response()->json($responseDocumentos);
                    }

                }else{
                    return false;
                }
            }else{                
                return false;
            }

        }else if($rowDataArray[0]['TipoID'] == '2') {//Se valida si el tipo de tramite es Prestamo Personal
            
            $parametro = 'DocumentosPrestamo';
            
            //Se obtiene la configuracion para obtener el tramite
            $responseParam = $this->obtenerValorPorParametro($parametro);

            if (!$responseParam) {
                return false;
            }
            
            $Fecha = $rowDataArray[0]['Fecha']; 
            $ID_CatalogoDocumentos = $responseParam;

            $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);
            
            if (!$responsePlantillas) {
                return false;
            }

            //se obtiene el prefijo para la ruta                                                
            $parametro = 'url_prestamosDirectos';

            $responsePrefijo = $this->obtenerValorPorParametro($parametro);

            if (!$responsePrefijo) {
                return false;
            }

            $Prefijo = $responsePrefijo;
            $Fecha = $rowDataArray[0]['Fecha'];
            $Vale = $rowDataArray[0]['DPVale'];
            $IdentificacionID = $rowDataArray[0]['IdentificacionID'];
            
            //Se seapara la Fecha
                            
            $dateComponents = date_parse($Fecha);

            $año = $dateComponents["year"];
            $mes = $dateComponents["month"];
            $mesNombre = $this->getNombreMes($mes);                
            $dia = $dateComponents["day"];
            
            $mesForm = sprintf("%02d", $dateComponents["month"]);
            $diaForm = sprintf("%02d", $dateComponents["day"]);
            $fechaCompleta = $año.$mesForm.$diaForm;
            
            $arrayDocumentos = [];

            for ($i = 0; $i < count($responsePlantillas); $i++) {
                $Codigo = $responsePlantillas[$i]->Codigo;
                $NombreDocumento = $responsePlantillas[$i]->NombreDocumento;
                $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/'.$NombreDocumento.'/'.$Codigo.$Vale;

                $arrayDocumentos[] = $rutaCompleta;
            }
                                                                        
            if (in_array($IdentificacionID, ['8', '2'])) {
                $rutaCompleta1 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-1';                    
                $arrayDocumentos[] = $rutaCompleta1;
                
                $rutaCompleta2 = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale.'-2';
                $arrayDocumentos[] = $rutaCompleta2;
            }else if($IdentificacionID == '3'){
                $rutaCompleta = $Prefijo.'/'.$año.'/'.$mesNombre.'/'.$fechaCompleta.'/IDENTIFICACION/I'.$Vale;                    
                $arrayDocumentos[] = $rutaCompleta;
            }

            if (count($rowDataArray) > 1) {                
                return $arrayDocumentos;
            } else {                                
                
                $req = new Request();
                $req->merge(['arrayDocumentos' => $arrayDocumentos, 'Vale' => $Vale, 'IdentificacionID' => $IdentificacionID]);

                $responseDocumentos = $this->getDocumentosAwsS3($req);
                return response()->json($responseDocumentos);
            }

        }else{            
            return false;
        }
        
    }

    public function getNombreMes($mesNumerico){
        $nombreMes;
        switch ($mesNumerico) {
            case 1:
                $nombreMes = "Enero";
                break;
            case 2:
                $nombreMes = "Febrero";
                break;
            case 3:
                $nombreMes = "Marzo";
                break;
            case 4:
                $nombreMes = "Abril";
                break;
            case 5:
                $nombreMes = "Mayo";
                break;
            case 6:
                $nombreMes = "Junio";
                break;
            case 7:
                $nombreMes = "Julio";
                break;
            case 8:
                $nombreMes = "Agosto";
                break;
            case 9:
                $nombreMes = "Septiembre";
                break;
            case 10:
                $nombreMes = "Octubre";
                break;
            case 11:
                $nombreMes = "Noviembre";
                break;
            case 12:
                $nombreMes = "Diciembre";
                break;
            default:
                $nombreMes = "Mes_inválido";
        }
    
        return $nombreMes;
    }

    
    public function getDocumentosAwsS3(Request $request){        
        //En $documentos se recibe el array con las rutas a los documentos
        $documentos = $request->input('arrayDocumentos');
        // dd($documentos);
        $Vale = $request->input('Vale');
        // dd($Vale);
        $IdentificacionID = $request->input('IdentificacionID');
// dd($IdentificacionID);
        if (count($documentos) == 1) {//Se controla para identificar peticion unitaria y generar ruta para identificacion
            // dd("entro");
            $caracter = '/';
            $ultimaPosicion = strrpos($documentos[0], $caracter); // Última posición del carácter
            $penultimaPosicion = strrpos($documentos[0], $caracter, $ultimaPosicion - strlen($documentos[0]) - 1);; // Penúltima posición del carácter
            $contenido = substr($documentos[0], $penultimaPosicion + 1, $ultimaPosicion - $penultimaPosicion - 1);
            
            if ($contenido == 'IDENTIFICACION') {
                
                $nuevaCadena = substr($documentos[0], 0, -6);
                $documentos = [];

                if (in_array($IdentificacionID, ['8', '2'])) {
                    for ($i=1; $i < 3; $i++) { 
                        $documentos[] = $nuevaCadena.'-'.$i;
                    }
                }else if($IdentificacionID == '3'){
                    $documentos[] = $nuevaCadena;
                }                
            }else{
                // dd("entro");
                $nuevaCadena = substr($documentos[0], 0, -4);
                
                $documentos = [];
                $documentos[] = $nuevaCadena;
                // dd($documentos);
            }
        }
        
        // Generar una URL para los archivos AWS
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $Bucket = env('AWS_BUCKET');

        /*
        $docsAws = [];
        //comandos para traer todos los archivos en el bucket
        try {
            $contents = $s3Client->listObjects([
                'Bucket' => $Bucket,
            ]);
            
            foreach ($contents['Contents'] as $content) {
                $docsAws[] = $content['Key'];
                //var_dump($content['Key']);
            }

        } catch (Exception $exception) {
            dd('fallo el comando');	
        }
        
        dd($docsAws);
        */
        
        $numFilesControl = 0;
        for ($i=0; $i < count($documentos); $i++) {             
            
            try {
                
                $nombrePDF = $documentos[$i].'.pdf';
                                
                $file = $s3Client->getObject([
                    'Bucket' => $Bucket,
                    'Key'    => $nombrePDF
                ]);

                $body = $file->get('Body'); //Se obtiene el archivo del bucket
                
                //Se guarda el archivo descargado en la ruta de disco preconfigurada
                Storage::disk('pdfs3')->put('Pdf'.($i + 1).'.pdf', $body);
                
            } catch (\Throwable $t){                            
                
                if (strpos($t->getMessage(), '404 Not Found') !== false) {
                    //El archivo no existe                                        
                    $numFilesControl++;                    
                    continue;                    
                }else{
                    $error = [
                        'status' => '0',
                        'fecha' => date('Y-m-d H:i:s'),
                        'descripcion' => 'Error en la funcion getDocumentosAwsS3() al consumir el servicio.',
                        'codigoError' => $t->getCode(),
                        'msnError' => $t->getMessage(),
                        'linea' => $t->getLine(),
                        'archivo' => $t->getFile(),
                        'requestLog' => $file,
                        'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
                    ];
                    Log::error('Error en la funcion getDocumentosAwsS3() al consumir el servicio', $error);
                    return false;
                }                
            }
        }
        // dd($numFilesControl);
        if ($numFilesControl == count($documentos)) {//Se valida si todos los archivos fallaron o no fueron encontrados
            return 'SinArchivos';
        }else{
            // dd($Vale);
            //Se obtiene el array con archivos en la carpeta de pdfs3
            $pdfFiles = glob(storage_path('pdfs3/*.pdf'));            
            //Se establece la ruta y nombre para guardar el archivo combinado        
            $outputPath = public_path('pdfs3Combinados/documentos-'.$Vale.'.pdf');
            
            //Se llama la funcion para la combinacion de pdfs y el guardado en la ruta outputPath
            // dd($pdfFiles);
            $result = $this->combinarPDFs($pdfFiles, $outputPath);
            // dd($result);
            // dd("entro");
            // dd($result);
            if (!$result) {
                // dd("entro");
                return false;
            }else{
                // dd("entro");
                // Se Eliminan archivos originales
                foreach ($pdfFiles as $file) {
                    unlink($file);
                }

                $arrayResponse = [
                    'pdf_url' => url('pdfs3Combinados/documentos-'.$Vale.'.pdf')                    
                ];

                return $arrayResponse;
            }
        }       
    }

    public function combinarPDFs($pdfFiles, $outputPath) {
        try {
            // dd("entro");
            $mpdf = new Mpdf();
            // dd($mpdf);
            
            foreach ($pdfFiles as $file) {
                                // dd($file);
                $pageCount = $mpdf->SetSourceFile($file);
                
                for ($page = 1; $page <= $pageCount; $page++) {
                    $mpdf->AddPage();
                    $tplId = $mpdf->ImportPage(($page));
                    $mpdf->UseTemplate($tplId);
                }
            }
            // dd($outputPath);
            //guardar el archivo combinado
            $mpdf->Output($outputPath, 'F');
            // dd("entro");
            return true;
        }catch (\Throwable $t){            
            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion combinarPDFs() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => "Error en PDF",
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion combinarPDFs() al consumir el servicio', $error);
            return false;
        }        
    }
    
    public function eliminarDocumento(){
        
        try {
            // dd("entro");
            //Se obtiene el array con archivos en la carpeta de pdfs3combinados
            $pdfFiles = glob(public_path('pdfs3Combinados/*.pdf'));
            // dd($pdfFiles);
            // Se Eliminan archivos originales
            foreach ($pdfFiles as $file) {
                unlink($file);
            }

            return true;
        }catch (\Throwable $t){            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion eliminarDocumento() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $pdfFiles,
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion eliminarDocumento() al consumir el servicio', $error);
            return false;
        }     
                
    }

    public function identificarDocumentosAwsS3($documentos, $Vale){        
        //En $documentos se recibe el array con las rutas a los documentos
        
        // Generar una URL prefirmada para los archivos
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $Bucket = env('AWS_BUCKET');

        //Se declara array para obtener los documentos faltantes
        $documentosExistentes = [];
        $documentosFaltantes = [];

        if (!empty($documentos)) {
            for ($i=0; $i < count($documentos); $i++) {             
            
                $nombrePDF = $documentos[$i].'.pdf';
                
                try {
                    
                    $file = $s3Client->getObject([
                        'Bucket' => $Bucket,
                        'Key'    => $nombrePDF
                    ]);
    
                    $documentosExistentes[] = $nombrePDF;                    
                                    
                } catch (\Throwable $t){                            
                                    
                    if (strpos($t->getMessage(), '404 Not Found') !== false) {                    
                        //El archivo no existe
                        $documentosFaltantes[] = $nombrePDF;
                        continue;                    
                    }else{
                        $error = [
                            'status' => '0',
                            'fecha' => date('Y-m-d H:i:s'),
                            'descripcion' => 'Error en la funcion identificarDocumentosAwsS3() al consumir el servicio.',
                            'codigoError' => $t->getCode(),
                            'msnError' => $t->getMessage(),
                            'linea' => $t->getLine(),
                            'archivo' => $t->getFile(),
                            'requestLog' => ['Bucket' => $Bucket, 'Key' => $nombrePDF],
                            'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
                        ];
                        Log::error('Error en la funcion identificarDocumentosAwsS3() al consumir el servicio', $error);
                        return false;
                    }                
                }
            }
        }
        
        return ['Existen' => $documentosExistentes, 'NoExisten' => $documentosFaltantes];              
    }

    public function obtenerTramite(Request $request){//para modales de ver detalles
        
        $rowDataArray = $request->input('rowDataArray');

        $DPVale = $rowDataArray[0]['DPVale'];
        $ClienteID = $rowDataArray[0]['ClienteID'];
        $Fecha = $rowDataArray[0]['Fecha'];

        //Se le agrega la Key para reutilizar la funcion del armado de rutas de archivo
        $rowDataArray[] = ['valid' => '1'];
        // dd($rowDataArray);
        //Se obtienen las rutas a los documentos
        $requestDocumentos = new Request();
        $requestDocumentos->merge(['rowDataArray' => $rowDataArray]);        
        $responseDocumentos = $this->getDocumentosS3($requestDocumentos);
        // dd($responseDocumentos);
        $identificacionDocumentos = $this->identificarDocumentosAwsS3($responseDocumentos, $DPVale);
        // dd($identificacionDocumentos);
        try {
            DB::enableQueryLog();            
            $responseTram = Configuracion::ObtenerTramite($DPVale);
            // dd($responseTram);
            //Desencritar y enmascarar tarjeta
            $responseTramite = $this->transformarTarjeta($responseTram[0], $Fecha);
                        
            $responseCustomer = $this->searchCustomer($ClienteID);
            
        }catch (\Throwable $t){            
            $queries = DB::getQueryLog();
                $lastQuery = end($queries); // Obtener la última consulta ejecutada
                $bindings = $lastQuery['bindings']; // Parámetros de la consulta
                $requestLog = $lastQuery['query']; // SQL de la consulta
                $error = [
                    'status' => '0',
                    'fecha' => date('Y-m-d H:i:s'),
                    'descripcion' => 'Error en la funcion obtenerTramite().',
                    'codigoError' => $t->getCode(),
                    'msnError' => $t->getMessage(),
                    'linea' => $t->getLine(),
                    'archivo' => $t->getFile(),
                    'requestLog' => $requestLog, // Agregar el SQL al log
                    'responseLog' => $bindings, // Agregar los parámetros al log
                ];
                Log::error('Error en la funcion obtenerTramite()', $error);
                return false;
        }

        if (!$responseTramite || !$responseCustomer || !$identificacionDocumentos) {
            return false;
        } else {
                        
            return response()->json([$responseTramite, $responseCustomer, $identificacionDocumentos]);
        }
    }

    public function searchCustomer($ClienteID, $data = null){
        
        try {
            
            //$ClienteID = '90262092';

            if ($ClienteID != null) {
                $data = [
                    'search-customer' => [
                        'data' => $ClienteID
                    ]
                ];
            }

            $rutaBroker = $this->obtenerValorPorParametro('url_broker');
            $sufijoRuta = $this->obtenerValorPorParametro('path_pos_s2credit');
                    
            $response = Http::post($rutaBroker.$sufijoRuta, $data);

            if ($response->successful()) {                
                
                $responseArray = $response->json();

                if (array_key_exists('ErrorMessage', $responseArray)) {
                    return $responseArray['ErrorMessage']['Mensaje'];
                } else {
                    if ($ClienteID != null) {                        
                        return $responseArray['results'];
                    } else {                        
                        return $responseArray;
                    }
                }

            }else{                
                return false;
            }
            
        }catch (\Throwable $t){            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion searchCustomer() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $data,
                'responseLog' => isset($response) ? $response : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion searchCustomer() al consumir el servicio', $error);
            return false;
        }
    }

    public function transformarTarjeta($responseTramite, $Fecha){
        
        $RoleID = session("RoleID");
        // dd($responseTramite->TarjetaClabe);
        if (!$responseTramite) {
            return false;
        }else{
            if (strlen($responseTramite->TarjetaClabe) > 16) {
                // dd("si entro");
                $responseTramite->TarjetaClabe = $this->descifrarTarjeta(1, $Fecha, base64_decode($responseTramite->TarjetaClabe));
            }
            // dd($responseTramite);
            if ($RoleID == '6') {
                //Se enmascara la tarjeta solo si el usuario es Rol 6 - Ventas
                $responseTramite->TarjetaClabe = $this->maskTarjeta($responseTramite->TarjetaClabe);
            }

        }
        // dd($responseTramite);g
        return $responseTramite;
    }

    //Función para emnascarar caracteres de la tarjeta
    public function maskTarjeta($TarjetaClabe){
        if (strlen($TarjetaClabe) == 16) {                    
            $maskedValue = substr($TarjetaClabe, 0, 6) . '******' . substr($TarjetaClabe, -4);            
            return $maskedValue;
        }else if(strlen($TarjetaClabe) > 16){
            $maskedValue = substr($TarjetaClabe, 0, 6) . '********' . substr($TarjetaClabe, -4);
            return $maskedValue;
        }else{
            return false;
        }
    }

    //Función para descifrar la tarjeta
    public function descifrarTarjeta($Indicador, $Fecha, $TarjetaClabe){
        
        try{
            DB::enableQueryLog();
            // dd($Indicador, $Fecha, $TarjetaClabe);
            if ($Indicador == 0) {
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
                if ($TarjetaClabe != null && $Fecha != null){                
                    $encryptName = $this->EncryptService->getEncrypt($Fecha);
                                        // dd($encryptName);
                    $resultDecrypt = $this->EncryptService->Encrypt($Indicador, $encryptName[0]->Nombre, $TarjetaClabe);
                    // dd($resultDecrypt);
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
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion descifrarTarjeta().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion descifrarTarjeta()', $error);
            return false;
        }        
    }


    public function reenviarSMS(Request $request){
        
        $rowDataArray = $request->input('globalRowDataArray');
        
        //se valida el tipo de tramite para definir el nombre del campo para validator
        $validatorResponse = [];

        $messages = [
            'required' => 'El campo telefono es requerido.',
            'string' => 'El dato telefono debe ser un string.',
            'max' => 'El dato telefono debe tener 13 caracteres.',
            'min' => 'El dato telefono debe tener 13 caracteres.',
            'starts_with' => 'El dato telefono debe iniciar con: 52.',
        ];

        $nameCampo;
        if ($rowDataArray[0]['TipoID'] == '6') {
            $nameCampo = 'inputCelEnvioEnlace26';
        }else{
            $nameCampo = 'inputCelEnvioEnlace136';
        }
        
        $validator = Validator::make($request->all(), [
            'telefonoEnvio' => 'required|string|max:12|min:12|starts_with:52',            
        ], $messages);

        if ($validator->fails()) {
            
            $errors = $validator->errors();
            $counter = 0;

            foreach($errors->all() as $message){
                $field = $nameCampo;                
                $validatorResponse[$field] = $message;
                $counter++;
            }
            
            $response["errors"] = $validatorResponse;            
            return response($response);
        }


        //si pasa la validacion de validator se continúa con el procedimiento de envio del SMS
        
        $telefonoEnvio = [$request->input('telefonoEnvio')];
        $FechaOriginal = $rowDataArray[0]['Fecha'];
        $DPVale = $rowDataArray[0]['DPVale'];

        $fecha = date("Y-m-d H:i:s", strtotime($FechaOriginal));
        $fecha = preg_replace("/[-: ]/", "", $fecha);
        
        $valeFecha = $DPVale . '|' . $fecha;
        
        $dominioUrl = $this->getUrlBeneficiario();
        $sufijoCifrado = $this->cifrar($valeFecha);

        $urlBeneficiario = $dominioUrl->original['url'].$sufijoCifrado->original['cifrado'];

        $mensaje = $urlBeneficiario;
        
        try {
            DB::enableQueryLog();
        
            $url_broker = Configuracion::obtenerValorPorParametro('url_broker');
            $path_sms = Configuracion::obtenerValorPorParametro('path_sms');
            
            $url = $url_broker . $path_sms;

            $response = Http::post($url, [
                'mensaje' => '¡Muchas gracias por tu confianza! Accede a este enlace para consultar y descargar los documentos de tu canje: '.$mensaje,
                'telefonos' => $telefonoEnvio
            ]);
        } catch (\Throwable $t) {
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion reenviarSMS() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $url,
                'responseLog' => isset($response) ? $response : 'No response', // Verificar si $response existe
            ];    
            Log::error('Error en la funcion enviarSMS() al consumir el servicio EnviarSMS', $error);
            return response()->json(['status' => 'error', 'message' => 'Error al enviar el SMS']);
        }
    
        if ($response->successful()) {
            return response()->json(['status' => 'success', 'data' => $response->json()]);
        }
    
        return response()->json(['status' => 'error', 'message' => 'Error al enviar el SMS']);


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
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getUrlBeneficiario().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion getUrlBeneficiario()', $error);            
            return response()->json(['url' => '']);
        }
    }

    public function cifrar($texto) {        
        $cifrado = Crypt::encryptString($texto);
        return response()->json(['cifrado' => $cifrado]);
    }


    public function generarySubirDocumento(Request $request){
                
        $validSubir = $request->input('validSubir'); //1 para generar y subir, 2 solo para subir                
        $rowDataArray;
        $tramiteDataArray;
        $searchCustomer;
        if ($validSubir == '1') {
            $rowDataArray = $request->input('rowDataArray'); //ObtenerTramites
            $tramiteDataArray = $request->input('tramiteDataArray'); //ObtenerTramite
            // dd($rowDataArray);
            $searchCustomer = $this->searchCustomer($rowDataArray[0]['ClienteID']);//search-customer

            if (!$searchCustomer) {
                return response()->json(['status' => false, 'prefijo' => '']);
            }
        }       
        
        $tipoDocumento = $request->input('tipoDocumento');
        $rutaDocumento = $request->input('rutaDocumento');
        
        $dataDocumentos = []; //array para almacenar las valores del documento que se va generar

        switch ($tipoDocumento) {
            case 'Ticket':
                $dataDocumentos['DistribuidorID'] = $rowDataArray[0]['DistribuidorID'];
                $dataDocumentos['NombreDistribuidor'] = $rowDataArray[0]['NombreDistribuidor'];
                $dataDocumentos['CodPlaza'] = $rowDataArray[0]['CodPlaza'];
                $dataDocumentos['TiendaID'] = $rowDataArray[0]['TiendaID'];
                $dataDocumentos['ClienteID'] = $rowDataArray[0]['ClienteID'];
                $dataDocumentos['NombreCliente'] = $rowDataArray[0]['NombreCliente'];
                $dataDocumentos['DPVale'] = $rowDataArray[0]['DPVale'];
                $dataDocumentos['Servicio'] = $rowDataArray[0]['Servicio'];
                $dataDocumentos['TarjetaClabe'] = $tramiteDataArray['TarjetaClabe'];// mostrar tarjeta sin enmascarar
                $dataDocumentos['Banco'] = $rowDataArray[0]['Banco'];
                $dataDocumentos['Importe'] = (float) $rowDataArray[0]['Importe'];
                $dataDocumentos['NoPlazo'] = $rowDataArray[0]['NoPlazo'];
                $dataDocumentos['PagoQuincenal'] = (float) $tramiteDataArray['PagoQuincenal'];
                $dataDocumentos['MontoSeguro'] = (float) $tramiteDataArray['MontoSeguro'];
                $dataDocumentos['PagoTotalQuincenal'] = $dataDocumentos['PagoQuincenal'] + $dataDocumentos['MontoSeguro'];
                $dataDocumentos['FIRMA'] = $tramiteDataArray['Firma'];

                $partes = explode(" ", $rowDataArray[0]['Fecha']);
                $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)
                $horaMinutos = $partes[1]; // $partes[1] contendrá la hora y los minutos (11:43:18.180)        
                $partesHoraMinutos = explode(":", $horaMinutos); // Separar la hora y los minutos
                $hora = $partesHoraMinutos[0]; // $partesHoraMinutos[0] contendrá la hora (11)
                $minutos = $partesHoraMinutos[1]; // $partesHoraMinutos[1] contendrá los minutos (43)                
                $fechaFormateada = $fecha;
                $horaFormateada = $hora.':'.$minutos;

                $dataDocumentos['Fecha'] = $fechaFormateada; //(formato aaaa-mm-dd)
                $dataDocumentos['Hora'] = $horaFormateada; //(formato hh:mm)

                //Generar documento y subirlo
                $Fecha = $rowDataArray[0]['Fecha']; 
                    
                $ID_CatalogoDocumentos = $this->getIDCatalogoDocumentos($rowDataArray);

                $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);

                $codigo;
                foreach ($responsePlantillas as $value) {
                    if ($value->NombreDocumento == strtoupper($tipoDocumento)) {
                        $codigo = $value->Codigo;
                        break;
                    }                        
                }
                
                $res = $this->generateDocument($codigo, $dataDocumentos, $rutaDocumento);

                return response()->json(['status' => $res, 'prefijo' => 'T']);
                
                break;
            case 'Seguro':
                $dataDocumentos['CodPlaza'] = $rowDataArray[0]['CodPlaza'];
                $dataDocumentos['TiendaID'] = $rowDataArray[0]['TiendaID'];
                
                $partes = explode(" ", $rowDataArray[0]['Fecha']);
                $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)                
                $fechaFormateada = $fecha;
                $dataDocumentos['Fecha'] = $fechaFormateada; //(formato aaaa-mm-dd)

                $dataDocumentos['DPVale'] = $rowDataArray[0]['DPVale'];
                $dataDocumentos['NombreCliente'] = $rowDataArray[0]['NombreCliente'];
                $dataDocumentos['RFC'] = $tramiteDataArray['RFC'];
                $dataDocumentos['Genero'] = $tramiteDataArray['Genero'];
                $dataDocumentos['Seguro'] = $tramiteDataArray['Seguro'];
                $dataDocumentos['MontoSeguro'] = (float) $tramiteDataArray['MontoSeguro'];
                $dataDocumentos['FechaInicio'] = $tramiteDataArray['FechaInicio'];
                $dataDocumentos['FechaFin'] = $tramiteDataArray['FechaFin'];
                $dataDocumentos['NoPoliza'] = $tramiteDataArray['NoPoliza'];
                $dataDocumentos['Beneficiario'] = $tramiteDataArray['Beneficiario'];
                $dataDocumentos['Firma'] = $tramiteDataArray['Firma'];

                //Generar documento y subirlo
                $Fecha = $rowDataArray[0]['Fecha']; 
                    
                $ID_CatalogoDocumentos = $this->getIDCatalogoDocumentos($rowDataArray);

                $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);

                $codigo;
                foreach ($responsePlantillas as $value) {
                    if ($value->NombreDocumento == strtoupper($tipoDocumento)) {
                        $codigo = $value->Codigo;
                        break;
                    }                        
                }
                
                $res = $this->generateDocument($codigo, $dataDocumentos, $rutaDocumento);

                return response()->json(['status' => $res, 'prefijo' => 'S']);
                
                break;
            case 'Pagare':
                $dataDocumentos['Plaza'] = $rowDataArray[0]['CodPlaza'];

                $partes = explode(" ", $rowDataArray[0]['Fecha']);
                $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)                
                $fechaFormateada = $fecha;
                $dataDocumentos['FechaTramite'] = $fechaFormateada; //(formato aaaa-mm-dd)
                $dataDocumentos['NombreDistribuidor'] = $rowDataArray[0]['NombreDistribuidor'];
                $dataDocumentos['Plaza'] = $rowDataArray[0]['CodPlaza'];
                
                $partes = explode(" ", $rowDataArray[0]['Fecha']);
                $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)                
                $fechaFormateada = $fecha;
                $dataDocumentos['FechaTramite'] = $fechaFormateada; //(formato aaaa-mm-dd)
                
                $dataDocumentos['MontoQuincenal'] = $tramiteDataArray['PagoQuincenal'];
                $dataDocumentos['NombreCliente'] = $rowDataArray[0]['NombreCliente'];
                // dd($dataDocumentos);
                // direccion de search-customer
                // dd($searchCustomer);
                $calle = $searchCustomer[0]['address']['street'];
                $numeroExt = $searchCustomer[0]['address']['house_number'];
                $numeroInt = $searchCustomer[0]['address']['apartment_number'];
                $colonia = $searchCustomer[0]['address']['neighborhood'];
                $dataDocumentos['Direccion'] = $calle.' No. '.$numeroExt.' int. '.$numeroInt.', colonia '.$colonia.'.';

                $dataDocumentos['Telefono'] = $searchCustomer[0]['phones'][0]['number'];
                $dataDocumentos['Ciudad'] = $searchCustomer[0]['address']['city'];
                $dataDocumentos['Firma'] = $tramiteDataArray['Firma'];

                //Generar documento y subirlo
                $Fecha = $rowDataArray[0]['Fecha']; 
                    
                $ID_CatalogoDocumentos = $this->getIDCatalogoDocumentos($rowDataArray);

                $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);

                $codigo;
                foreach ($responsePlantillas as $value) {
                    if ($value->NombreDocumento == strtoupper($tipoDocumento)) {
                        $codigo = $value->Codigo;
                        break;
                    }                        
                }
                
                $res = $this->generateDocument($codigo, $dataDocumentos, $rutaDocumento);

                return response()->json(['status' => $res, 'prefijo' => 'P']);
                
                break;
            case 'Futuro':
                
                $dataDocumentos['NOMBRE'] = $searchCustomer[0]['name'].' '.$searchCustomer[0]['middleName'];
                $dataDocumentos['APELLIDOPATERNO'] = $searchCustomer[0]['lastName'];
                $dataDocumentos['APELLIDOMATERNO'] = $searchCustomer[0]['secondLastName'];

                //para obtener Nacionalidad
                $data = [
                    'paises' => []
                ];

                $responseNacionalidad = $this->searchCustomer(null, $data);

                foreach ($responseNacionalidad['paises'] as $pais) {
                    if ($pais['id_pais'] == $searchCustomer[0]['Campos_PLD']['id_nacionalidad']) {
                        $dataDocumentos['NACIONALIDAD'] = $pais['pais'];
                        break;
                    }
                }
                
                // direccion de search-customer
                $calle = $searchCustomer[0]['address']['street'];
                $numeroExt = $searchCustomer[0]['address']['house_number'];
                $numeroInt = $searchCustomer[0]['address']['apartment_number'];                 
                $dataDocumentos['DOMICILIO'] = $calle.', No. '.$numeroExt.', int. '.$numeroInt.'.';
                $dataDocumentos['COLONIA'] = $searchCustomer[0]['address']['neighborhood'];
                $dataDocumentos['CP'] = $searchCustomer[0]['address']['zipcode'];
                $dataDocumentos['MUNICIPIO'] = $searchCustomer[0]['address']['settlement'];
                $dataDocumentos['CIUDAD'] = $searchCustomer[0]['address']['city'];
                $dataDocumentos['ESTADO'] = $searchCustomer[0]['address']['state'];
                $dataDocumentos['PAIS'] = $searchCustomer[0]['birthCountry'];
                $dataDocumentos['TELEFONO'] = $searchCustomer[0]['phones'][0]['number'];
                $dataDocumentos['CORREO'] = $searchCustomer[0]['email'];
                $dataDocumentos['RFC'] = $searchCustomer[0]['rfc'];
                $dataDocumentos['GENERO'] = $searchCustomer[0]['genderString'];
                $dataDocumentos['NACIMIENTO'] = $searchCustomer[0]['birthdate'];
                $dataDocumentos['CURP'] = $searchCustomer[0]['curp'];
                
                //para obtener EntidadNacimiento
                $data = [
                    'estados' => []
                ];

                $responseEstados = $this->searchCustomer(null, $data);

                foreach ($responseEstados['estados'] as $estado) {
                    if ($estado['id_estado'] == $searchCustomer[0]['Campos_PLD']['id_estado_nacimiento']) {
                        $dataDocumentos['ENTIDADNACIMIENTO'] = $estado['nombre'];
                        break;
                    }
                }

                //para obtener PaisNacimiento
                $data = [
                    'paises' => []
                ];

                $responsePaises = $this->searchCustomer(null, $data);

                foreach ($responsePaises['paises'] as $pais) {
                    if ($pais['id_pais'] == $searchCustomer[0]['Campos_PLD']['id_pais_nacimiento']) {
                        $dataDocumentos['PAISNACIMIENTO'] = $pais['pais'];
                        break;
                    }
                }
                
                //para obtener OrigenRecursos
                $data = [
                    'profession' => []
                ];

                $responseProfession = $this->searchCustomer(null, $data);

                foreach ($responseProfession['data'] as $profession) {
                    if ($profession['id'] == $searchCustomer[0]['Campos_PLD']['id_profesion']) {
                        $dataDocumentos['ORIGENRECURSOS'] = $profession['value'];
                        break;
                    }
                }

                //para obtener DestinoRecursos
                $data = [
                    'destino_recursos' => []
                ];

                $responseDestino = $this->searchCustomer(null, $data);

                foreach ($responseDestino['destino_recursos'] as $destino) {
                    if ($destino['id_destino_recursos'] == $searchCustomer[0]['Campos_PLD']['id_destino_recurso']) {
                        $dataDocumentos['DESTINORECURSOS'] = $destino['destino_recursos'];
                        break;
                    }
                }                
                
                $TipoIdentificacion;
                switch ($rowDataArray[0]['IdentificacionID']) {
                    case '2':
                        $TipoIdentificacion = 
                            "☑INE    ☐Pasaporte    ☐ Licencia     ☐Cédula Profesional";
                        break;
                    case '3':
                        $TipoIdentificacion = 
                            "☐INE    ☑Pasaporte    ☐ Licencia     ☐Cédula Profesional";
                        break;
                    case '8':
                        $TipoIdentificacion = 
                            "☐INE    ☐Pasaporte    ☑ Licencia     ☐Cédula Profesional";
                        break;
                    case '4':
                        $TipoIdentificacion = 
                            "☐INE    ☐Pasaporte    ☐ Licencia     ☑Cédula Profesional";
                        break;                    
                }
                
                $dataDocumentos['TIPOIDENTIFICACION'] = $TipoIdentificacion;
                $dataDocumentos['IDENTIFICACION'] = $tramiteDataArray['Identificacion'];
                $dataDocumentos['COLABORADOR'] = $tramiteDataArray['UsuarioID'];
                $dataDocumentos['NOMBRECOLABORADOR'] = $tramiteDataArray['NombreUsuario'];
                
                $partes = explode(" ", $rowDataArray[0]['Fecha']);
                $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)                
                $fechaFormateada = $fecha;
                $dataDocumentos['FECHA'] = $fechaFormateada; //(formato aaaa-mm-dd)

                $dataDocumentos['FIRMA'] = $tramiteDataArray['Firma'];

                //Generar documento y subirlo
                $Fecha = $rowDataArray[0]['Fecha']; 
                    
                $ID_CatalogoDocumentos = $this->getIDCatalogoDocumentos($rowDataArray);

                $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);

                $codigo;

                foreach ($responsePlantillas as $value) {
                    if ($value->NombreDocumento == strtoupper($tipoDocumento)) {
                        $codigo = $value->Codigo;
                        break;
                    }                        
                }
                
                $res = $this->generateDocument($codigo, $dataDocumentos, $rutaDocumento);

                return response()->json(['status' => $res, 'prefijo' => 'F']);

                break;
            case 'Vale':

                if ($validSubir == '1') {
                    $dataDocumentos['FOLIO'] = $rowDataArray[0]['DPVale'];
                    $dataDocumentos['DISTRIBUIDOR ID'] = $rowDataArray[0]['DistribuidorID'];
                                    
                    $partes = explode(" ", $rowDataArray[0]['Fecha']);
                    $fecha = $partes[0]; // $partes[0] contendrá la fecha (2023-08-15)                
                    $fechaFormateada = $fecha;
                    $dataDocumentos['FECHA'] = $fechaFormateada; //(formato aaaa-mm-dd)

                    $dataDocumentos['SURTASE'] = $rowDataArray[0]['NombreCliente'];
                    
                    // direccion de search-customer
                    $calle = $searchCustomer[0]['address']['street'];
                    $numeroExt = $searchCustomer[0]['address']['house_number'];
                    $numeroInt = $searchCustomer[0]['address']['apartment_number'];
                    $colonia = $searchCustomer[0]['address']['neighborhood'];
                    $dataDocumentos['DOMICILIO'] = $calle.', No. '.$numeroExt.', int. '.$numeroInt.', colonia '.$colonia.'.';
                    
                    $dataDocumentos['TEL'] = $searchCustomer[0]['phones'][0]['number'];
                    $dataDocumentos['CLIENTE'] = $rowDataArray[0]['NombreCliente'];
                    $dataDocumentos['FIRMA'] = $tramiteDataArray['Firma'];

                    //Generar documento y subirlo
                    $Fecha = $rowDataArray[0]['Fecha']; 
                    
                    $ID_CatalogoDocumentos = $this->getIDCatalogoDocumentos($rowDataArray);

                    $responsePlantillas = $this->getPlantillas($Fecha, $ID_CatalogoDocumentos);

                    $codigo;
                    foreach ($responsePlantillas as $value) {
                        if ($value->NombreDocumento == strtoupper($tipoDocumento)) {
                            $codigo = $value->Codigo;
                            break;
                        }                        
                    }

                    $res = $this->generateDocument($codigo, $dataDocumentos, $rutaDocumento);

                    return response()->json(['status' => $res, 'prefijo' => 'V']);

                }else if($validSubir == '2'){
                    
                    $pdfFile = $request->file('file1');
                    
                    $result = $this->subirPdfAws($pdfFile, $rutaDocumento);

                    //return $result;
                    return response()->json(['status'=> $result, 'prefijo' => 'V']);
                                        
                }

                break;
            case 'Identificacion':
                    
                    $IdentificacionID = $request->input('identificacionID');
                    
                    $rutaSinSufijo = substr($rutaDocumento, 0, -6);
                    
                    if (in_array($IdentificacionID, ['8', '2'])) {
                        
                        $pdfFile2 = $request->file('file2');
                        $pdfFile3 = $request->file('file3');

                        $result1 = $this->subirPdfAws($pdfFile2, $rutaSinSufijo.'-1.pdf');
                        $result2 = $this->subirPdfAws($pdfFile3, $rutaSinSufijo.'-2.pdf');

                        if ($result1 == true && $result2 == true) {
                            //return true;
                            return response()->json(['status'=> true, 'prefijo' => 'I']);
                        } else {
                            //return false;
                            return response()->json(['status'=> false, 'prefijo' => 'I']);
                        }
                    }else if($IdentificacionID == '3'){
                        
                        $pdfFile1 = $request->file('file1');
                                                
                        $result1 = $this->subirPdfAws($pdfFile1, $rutaSinSufijo);                        

                        if ($result1 == true) {
                            //return true;
                            return response()->json(['status'=> true, 'prefijo' => 'I']);
                        } else {
                            //return false;
                            return response()->json(['status'=> false, 'prefijo' => 'I']);
                        }
                    }                   
                    
                break;
            default:
                return response()->json(['status' => false, 'prefijo' => '']);
                break;
        }
    }

    public function subirPdfAws($pdfFile, $rutaNombre){
        
        // Generar una URL para los archivos AWS
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        $Bucket = env('AWS_BUCKET');

        try {
                    
            $file = $s3Client->putObject([
                'Bucket' => $Bucket,
                'Key'    => $rutaNombre,
                'SourceFile' => $pdfFile
            ]);            
            
            return true;
                            
        } catch (\Throwable $t){                            
                            
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion subirPdfAws() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $file,
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion subirPdfAws() al consumir el servicio', $error);
            return false;
        }
    }

    public function getIDCatalogoDocumentos($rowDataArray){
        
        if (in_array($rowDataArray[0]['TipoID'], ['1', '3'])) {//Se valida si el tipo de tramite es Vale Financiero o Revolvente
            
            //Se valida si el vale es numerico o alfanumerico
            $valeFisico;
            $valeElectronico;
            if (ctype_alnum($rowDataArray[0]['DPVale'])) {
                if (ctype_digit($rowDataArray[0]['DPVale'])) {//Se valida si el vale es Numerico
                    $valeFisico = true;
                    $valeElectronico = false;
                } else {//Se valida si el vale es AlfaNumerico
                    $valeFisico = false;
                    $valeElectronico = true;                    
                }
            }
            
            if ($valeFisico) { //Se valida si el vale es fisico
                if ($rowDataArray[0]['SociedadID'] == '1') {//CDPT
                
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {                        
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentosValeCDPT';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeCDPTSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
                    
                    return $responseParam;

                } else if ($rowDataArray[0]['SociedadID'] == '2') {//SOFOM
                    
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {                        
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentoValeSOFOM';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentoValeSOFOMSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    return $responseParam;
                }
            } else if ($valeElectronico){ //Se valida si es vale electrónico (alfanumerico)
                if ($rowDataArray[0]['SociedadID'] == '1') {//CDPT
                
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentosValeCDPTElectronico';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeCDPTElectronicoSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    return $responseParam;

                } else if ($rowDataArray[0]['SociedadID'] == '2') {//SOFOM
                    //Se valida si tuvo seguro el tramite			
                    $DPVale = $rowDataArray[0]['DPVale'];
                    $responseSeguro = $this->getSeguro($DPVale);
    
                    $parametro;
                    if (!$responseSeguro) {
                        return false;
                    } else {                    
                        if ($responseSeguro[0]->status == '0') {                                        
                            $parametro = 'DocumentoValeSOFOMElectronico';
                        } else if ($responseSeguro[0]->status == '1') {                                        
                            $parametro = 'DocumentosValeSOFOMElectronicoSeguro';
                        }
                    }
    
                    //Se obtiene la configuracion para obtener el tramite
                    $responseParam = $this->obtenerValorPorParametro($parametro);
    
                    if (!$responseParam) {
                        return false;
                    }
    
                    return $responseParam;
    
                }else{
                    return false;
                }
            }else{                
                return false;
            }

        }else if($rowDataArray[0]['TipoID'] == '2') {//Se valida si el tipo de tramite es Prestamo Personal
            
            $parametro = 'DocumentosPrestamo';
            
            //Se obtiene la configuracion para obtener el tramite
            $responseParam = $this->obtenerValorPorParametro($parametro);

            if (!$responseParam) {
                return false;
            }                        
            
            return $responseParam;
        }
    }

    public function generateDocument($codigo, $variables, $rutaDocumento) {
                
        try {
            // Ruta de la plantilla
            $templatePath = public_path("assets/Templates/" . $codigo . ".docx");
        
            // Crea una nueva instancia de TemplateProcessor
            $templateProcessor = new TemplateProcessor($templatePath);
        
            // Reemplaza las variables en el documento
            foreach ($variables as $key => $value) {
                // Comprueba si el valor es una imagen en base64
                if (substr($value, 0, 11) == 'data:image/') {
                    // Extrae la parte de base64 de la cadena
                    $base64Image = substr($value, strpos($value, ',') + 1);
                    // Decodifica la imagen en base64
                    $image = base64_decode($base64Image);
        
                    // Crea un archivo temporal y guarda la imagen en él
                    $tempImageFilename = tempnam(sys_get_temp_dir(), 'phpword');
                    file_put_contents($tempImageFilename, $image);
        
                    // Establece la imagen en el documento
                    if ($key == 'Firma' || $key == 'FIRMA') {
                        $templateProcessor->setImageValue($key, array('path' => $tempImageFilename));
                    } else {
                        $templateProcessor->setImageValue($key, array('path' => $tempImageFilename, 'width' => 300, 'height' => 300, 'ratio' => false));
                    }
                    unlink($tempImageFilename);
                } else {
                    $templateProcessor->setValue($key, $value);
                }
            }
        
            // Guarda el documento procesado en un archivo temporal
            $tempDocFilename = tempnam(sys_get_temp_dir(), 'phpword');
            $templateProcessor->saveAs($tempDocFilename);
            
            // Convertir el archivo .docx a .pdf usando LibreOffice
            $outputPdfPath = tempnam(sys_get_temp_dir(), 'converted_pdf_');
            
            $os = strtoupper(substr(PHP_OS, 0, 3));            
            
            if ($os === 'WIN') {
                // Comando para Windows
                shell_exec('"C:/Program Files/LibreOffice/program/soffice.exe" --headless --convert-to pdf:writer_pdf_Export --outdir "' . sys_get_temp_dir() . '" "' . $tempDocFilename . '"');
            } else {
                // Comando para Linux                                
                shell_exec('env HOME=/tmp /opt/libreoffice7.6/program/soffice --headless --convert-to pdf:writer_pdf_Export --outdir "' . sys_get_temp_dir() . '" "' . $tempDocFilename . '"');                
            }
            
            $newFilePath = sys_get_temp_dir() . '/' . pathinfo($tempDocFilename, PATHINFO_FILENAME) . '.pdf';

            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
            
            $Bucket = env('AWS_BUCKET');
                        
            $s3Client->putObject([
                'Bucket' => $Bucket,
                'Key' => $rutaDocumento[0],
                'SourceFile' => $newFilePath
            ]);

            // Elimina los archivos temporales
            unlink($tempDocFilename);
            unlink($outputPdfPath);
            unlink($newFilePath);
            
            return true; 
                            
        } catch (\Throwable $t){                            
                        
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion generateDocument() al consumir el servicio.',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $s3Client,
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion generateDocument() al consumir el servicio', $error);
            return false;
        }
    }

    public function cambiarEstatusDocumentos(Request $request){
        
        $DPVale = $request->input('DPVale');
        $DocEstatus = $request->input('DocEstatus');
        
        try {
            DB::enableQueryLog();
            $response = Configuracion::CambiarEstatusDocumentos($DPVale, $DocEstatus);

            return response()->json($response);
                            
        } catch (\Throwable $t){                            
                        
            $queries = DB::getQueryLog();
            $lastQuery = end($queries); // Obtener la última consulta ejecutada
            $bindings = $lastQuery['bindings']; // Parámetros de la consulta
            $requestLog = $lastQuery['query']; // SQL de la consulta
            $error = [
                'status' => '0',
                'fecha' => date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion cambiarEstatusDocumentos().',
                'codigoError' => $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestLog, // Agregar el SQL al log
                'responseLog' => $bindings, // Agregar los parámetros al log
            ];
            Log::error('Error en la funcion cambiarEstatusDocumentos()', $error);
            return false;
        }
    }
    
    public function exportTable(Request $request)
    {        
        try{
            $data = $request->input('arrayData');
            $typeExport = $request->input('typeExport');
            
            $RoleID = session("RoleID");
            $dataSession;
            
            if ($RoleID == '6') {
                $dataSession = session()->all();
            }else{
                $dataSession = session("USERDATA");
            }
            
            $usuarioActivo = $dataSession['Nombre'];
                    
            // Crear un nuevo objeto Spreadsheet
            $spreadsheet = new Spreadsheet();
            
            // Obtener la hoja de trabajo activa
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setTitle("TablaPrestamos");

            //Setear como texto todas las columnas al exportar
            $sheet->getStyle('A:O')->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

            $sheet->mergeCells('A1:O1');
            $sheet->mergeCells('A2:O2');
            $sheet->mergeCells('A3:O3');
            $sheet->mergeCells('A4:O4');

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
            $sheet->setCellValue('A2', 'Reporte Financiero');
            $sheet->setCellValue('A3', 'Elaborado por el usuario: '.$usuarioActivo);//nombre del colaborador que exporto la tabla
            $sheet->setCellValue('A4', 'Fecha: '.date("d-m-Y"));
            
            $spreadsheet->getActiveSheet()->getCell('A5')->setValue('');
                    
            $sheet->setCellValue('A6', 'Plaza');
            $sheet->setCellValue('B6', 'Tienda');
            $sheet->setCellValue('C6', 'Fecha');
            $sheet->setCellValue('D6', 'Hora');
            $sheet->setCellValue('E6', 'Rev. Ofi.');
            $sheet->setCellValue('F6', 'Rev. Aud.');
            $sheet->setCellValue('G6', 'Tipo');
            $sheet->setCellValue('H6', 'Folio');
            $sheet->setCellValue('I6', 'Importe');
            $sheet->setCellValue('J6', 'Banco');            
            $sheet->setCellValue('K6', 'ID Cliente');
            $sheet->setCellValue('L6', 'Nombre Cliente');
            $sheet->setCellValue('M6', 'Celular');            
            $sheet->setCellValue('N6', 'ID Distribuidor');
            $sheet->setCellValue('O6', 'Nombre Distribuidor');
            

            $styleArrayTHead = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]            
            ];
            
            $sheet->getCell('A6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('B6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('C6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('D6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('E6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('F6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('G6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('H6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('I6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('J6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('K6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('L6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('M6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('N6')->getStyle()->applyFromArray($styleArrayTHead);
            $sheet->getCell('O6')->getStyle()->applyFromArray($styleArrayTHead);

            $row = 7;        
            foreach ($data as $item) {                       
                
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $sheet->setCellValue('C' . $row, $item[2]);
                $sheet->setCellValue('D' . $row, $item[3]);
                $sheet->setCellValue('E' . $row, $item[4]);
                $sheet->setCellValue('F' . $row, $item[5]);
                $sheet->setCellValue('G' . $row, $item[6]);
                $sheet->setCellValue('H' . $row, $item[7]);
                $sheet->setCellValue('I' . $row, $item[8]);
                $sheet->setCellValue('J' . $row, $item[9]);
                $sheet->setCellValue('K' . $row, $item[10]);
                $sheet->setCellValue('L' . $row, $item[11]);
                $sheet->setCellValue('M' . $row, (string) $item[12]);
                $sheet->setCellValue('N' . $row, $item[13]);
                $sheet->setCellValue('O' . $row, $item[14]); 
                                
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
                    
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(20);
            $sheet->getColumnDimension('J')->setWidth(20);
            $sheet->getColumnDimension('K')->setWidth(20);
            $sheet->getColumnDimension('L')->setWidth(20);
            $sheet->getColumnDimension('M')->setWidth(20);
            $sheet->getColumnDimension('N')->setWidth(20);
            $sheet->getColumnDimension('O')->setWidth(20);
                        
            
            if ($typeExport == 1) {
                // Crear un objeto Writer para guardar el archivo Excel
                $writer = new Xlsx($spreadsheet);                
                // Guardar el archivo Excel en la ubicación deseada            
                $excelPath = public_path('ReporteFinanciero-'.date("d-m-Y").'.xlsx'); // ubicación y nombre de archivo deseados            
                $writer->save($excelPath);            
     
                return response()->download($excelPath,'ReporteFinanciero-'.date("d-m-Y").'.xlsx', [
                    'Content-Type' => 'application/vndopenxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend();
            }elseif ($typeExport == 2){ 
                
                $contenidoHtml = '<table>';

                $contenidoHtml .= '<thead>';
                $contenidoHtml .= '<tr>';
                    $contenidoHtml .= '<th>Plaza</th>';
                    $contenidoHtml .= '<th>Tienda</th>';
                    $contenidoHtml .= '<th>Fecha</th>';
                    $contenidoHtml .= '<th>Hora</th>';
                    $contenidoHtml .= '<th>Rev. Ofi.</th>';
                    $contenidoHtml .= '<th>Rev. Aud.</th>';
                    $contenidoHtml .= '<th>Tipo</th>';
                    $contenidoHtml .= '<th>Folio</th>';
                    $contenidoHtml .= '<th>Importe</th>';
                    $contenidoHtml .= '<th>Banco</th>';                    
                    $contenidoHtml .= '<th>ID Cliente</th>';
                    $contenidoHtml .= '<th>Nombre Cliente</th>';
                    $contenidoHtml .= '<th>Celular</th>';
                    $contenidoHtml .= '<th>ID Distribuidor</th>';
                    $contenidoHtml .= '<th>Nombre Distribuidor</th>';
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
                    $contenidoHtml .= '<td>' . $fila[6]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[7]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[8]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[9]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[10]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[11]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[12]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[13]. '</td>';
                    $contenidoHtml .= '<td>' . $fila[14]. '</td>';
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
                            font-size: 12px;
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
                                <div class="t2"><strong>Reporte Financiero</strong></div>
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
                        DP Reporte Financiero
                    </div>
                    </body>
                    </html>
                    HTML;
                
                $mpdf = new Mpdf();

                // Agregar el contenido HTML a mPDF
                $mpdf->WriteHTML($plantillaHtml);

                // Guardar el archivo Pdf en la ubicación deseada
                $pdfPath = public_path('ReporteFinanciero-'.date("d-m-Y").'.pdf'); // ubicación y nombre de archivo deseados
                $mpdf->Output($pdfPath, 'F');
                
                return response()->download($pdfPath,'ReporteFinanciero-'.date("d-m-Y").'.pdf', [
                    'Content-Type' => 'application/pdf',
                ])->deleteFileAfterSend();
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
                'requestLog' => $mpdf,
                'responseLog' => isset($result) ? $result : 'No response', // Verificar si $response existe
            ];
            Log::error('Error en la funcion canHaveLoan() al consumir el servicio', $error);
            return false;
        }
        
    }


}
