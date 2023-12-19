<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\ConfigService;
use App\Services\PreCargaPreDirectosService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PreCargaPreDirectosService
{
    private $url_broker;
    private $path_pos_s2credit;

    public function __construct(){
        $this->url_broker = Configuracion::obtenerValorPorParametro('url_broker');
        $this->path_pos_s2credit = Configuracion::obtenerValorPorParametro('path_pos_s2credit');
    }
    public function getCatalogoServicios()
    {
        try {
            DB::enableQueryLog();
            $codPlaza = session('CodPlaza');
    
            $results = DB::select('EXEC getCatalogoServicios :codPlaza', ['codPlaza' => $codPlaza]);
    // $results = [];
    // dd($results);

            // Verificar si los resultados están vacíos
            if (empty($results)) {
                return ['error' => 'No se encontraron servicios registrados para la plaza. Contacte al administrador.'];
            }
            return $results;
        } catch (\PDOException $e) {
            // Esto captura errores específicos de PDO (relacionados con la base de datos)
            // Manejar caso a
            return $this->handleException($e, 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.','Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.');
        } catch (\Throwable $t) {
            // Captura otros errores generales
            return $this->handleException($t, 'Ocurrió un error inesperado. Contacte al administrador.');
        }
    }
    
    public static function handleException($exception,$functionDescription, $customMessage = '', )
    {
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta
    
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => $functionDescription,            
            'codigoError'=> $exception->getCode(),
            'msnError' => $exception->getMessage(),
            'linea' => $exception->getLine(),
            'archivo' => $exception->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,
        ];    
        Log::error($functionDescription, $error);
    
        return ['error' => $customMessage];
    }
    
    
    public function getBranches()
    {
        $requestData = [
            'branches' => []
        ];
        $response = null;
        try {
            // $response = Http::post($this->url_broker . $this->path_pos_s2credit, [
            //     'branches' => []
            // ]);            
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
                'responseLog' => $response,
            ];    
            Log::error('Error en la funcion getBranches() al consumir el servicio s2credit', $error);
            return false;
        }
    
        if ($response->successful()) {
            return $response->json();
        }

    
        return false;
    }
    public function getStores($branchId)
    {
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
                        //    $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
                // dd($response);
            //                 $client = new Client(['timeout' => 2.0]);
            // $response = $client->post(ConfigService::obtenerUrlS2Credit(), [
            //     'json' => $data
            // ]);
            // $body = json_decode($response->getBody(), true);
            // Revisamos si la respuesta fue exitosa
            if ($response->successful()) {
                // Obtenemos el cuerpo de la respuesta
                $data = $response->json();
    
                // Si no hay datos, devolvemos un error específico
                if (empty($data)) {
                    return ['error' => 'No se encontraron registros de tiendas. Favor de intentarlo de nuevo.'];
                }
    
                // Si todo sale bien, devolvemos los datos
                return $data;
            }
    
            // Si la respuesta no fue exitosa, devolvemos un error
            return ['error' => 'Ocurrió un error al obtener el catálogo de Tiendas.'];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error de timeout en la función getStores() al consumir el servicio s2credit',
                'codigoError'=> $e->getCode(),
                'msnError' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Timeout en la función getStores() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al obtener catalogo de tiendas, favor de volver a intentar.'];
    
        } catch (\Throwable $t) {
            $error = [
                'status'=> '0',
                'fecha'=> date('Y-m-d H:i:s'),
                'descripcion' => 'Error en la funcion getStores() al consumir el servicio s2credit',                
                'codigoError'=> $t->getCode(),
                'msnError' => $t->getMessage(),
                'linea' => $t->getLine(),
                'archivo' => $t->getFile(),
                'requestLog' => $requestData,
                'responseLog' => $response,
            ];    
            Log::error('Error en la funcion getStores() al consumir el servicio s2credit', $error);
            return ['error' => 'Ocurrió un error al obtener el catálogo de Tiendas. Favor de intentarlo de nuevo.'];
        }
    }
    public static function getCatalogoIdentificaciones()
    {
        try {
            DB::enableQueryLog();
            $results = DB::select('exec dbo.getCatalogoIdentificaciones');
            // $results = DB::connection('prueba')->select('exec dbo.getCatalogoIdentificaciones');
            // $results =[];
    
            // Puedes agregar una verificación para los resultados vacíos si es necesario, como hicimos en el caso anterior
            if (empty($results)) {
                return ['error' => 'No se encontró la configuración. Contacte al administrador'];
            }
            
            return $results;
    
        } catch (\PDOException $e) {
            return self::handleException($e, 'Error en la función getCatalogoIdentificaciones() del Servicio PreCargaPreDirectosService.', 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.');
    
        } catch (\Throwable $t) {
            return self::handleException($t, 'Ocurrió un error inesperado. Contacte al administrador.', 'Error en la función getCatalogoIdentificaciones() del Modelo Configuracion');
        }    
    }
    
public static function getIdentificaciones()
{
    try{
        DB::enableQueryLog();
        // return DB::select("SELECT * FROM dbo.Configuracion WHERE Parametro ='IdentificacionesID'");
        $results = DB::select("SELECT * FROM dbo.Configuracion WHERE Parametro ='IdentificacionesID'");
        // $results = [];
        // $results = DB::connection('prueba')->select("SELECT * FROM dbo.Configuracion WHERE Parametro ='IdentificacionesID'");

        if(empty($results)){
            return ['error' => 'No se encontraron tipos de indentificaciones registradas. Contacte al administrador.'];
        }
        return $results;
    } catch (\PDOException $e) {
        return self::handleException($e, 'Error al.', 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.');

    }catch(\Throwable $t){
        $queries = DB::getQueryLog();
        $lastQuery = end($queries); // Obtener la última consulta ejecutada
        $bindings = $lastQuery['bindings']; // Parámetros de la consulta
        $requestLog = $lastQuery['query']; // SQL de la consulta

        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion getCatalogoIdentificaciones() del Modelo Configuracion',            
            'codigoError'=> $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $requestLog,
            'responseLog' => $bindings,

        ];    
        Log::error('Error en la funcion getCatalogoIdentificaciones() del Modelo Configuracion', $error);
    }
}
public static function getCatalogoBancos()
{
    try {
        DB::enableQueryLog();
        $results= DB::select('exec dbo.getCatalogoBancos');
        // $results = DB::connection('prueba')->select('exec dbo.getCatalogoBancos');
        // $results =[];
        if (empty($results)) {
            return ['error' => 'No se encontraron bancos registrados para la plaza. Contacte al administrador.'];
        }
        return $results;
        } catch (\PDOException $e) {
            return self::handleException($e, 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.','Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.');

    } catch (\Throwable $t) {
        return self::handleException($t, 'Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.', 'Error en la función getCatalogoBancos() del Modelo Configuracion');
    }    
}
public static function getMontoMinPresDis()
{
    try{
        DB::enableQueryLog();
        $results = DB::select("SELECT * FROM dbo.Configuracion WHERE Parametro ='MontoMinPresDis'");
        // $results = DB::connection('prueba')->select("SELECT * FROM dbo.Configuracion WHERE Parametro ='MontoMinPresDis'");
        // $results =[];
        if (empty($results)) {
            return ['error' => 'No se encontró el monto minimo registrado para la plaza. Contacte al administrador.'];
        }
        return $results;

    }catch(\PDOException $e){
        return self::handleException($e, 'Error en la función getMontoMinPresDis() del Modelo Configuracion.','Ocurrió un error al conectar con la base de datos SQL. Contacte al administrador.');
    }catch(\Throwable $t){
        return self::handleException($t, 'Error en la función getMontoMinPresDis() del Modelo Configuracion.', 'Error en la función getMontoMinPresDis() del Modelo Configuracion');
    }
    
}

public static function save($data)
{
    $apiUrl = ConfigService::obtenerUrlS2Credit();

    $response = null;
    try {
        // Realizar la petición POST con los datos
        $response = Http::post($apiUrl, $data);
                                // $response = Http::timeout(5)->get('https://httpbin.org/delay/60');
            // $response = [];
                // dd($response);
                if(empty($response)){
                    return ['error' => 'No se obtuvo respuesta por parte del servicio. Contacte al administrador.'];
                }
                
        return $response->json();
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $error = [
            'status'=> '0',
            'fecha'=> date('Y-m-d H:i:s'),
            'descripcion' => 'Error de timeout en la función getBranches() al consumir el servicio s2credit',
            'codigoError'=> $e->getCode(),
            'msnError' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
            'requestLog' => $apiUrl,
            'responseLog' => $response,
        ];    
        Log::error('Timeout en la función getBranches() al consumir el servicio s2credit', $error);
        return ['error' => 'Ocurrió un error al conectarse con el servicio, favor de volver a intentar.'];

    } catch (\Throwable $t) {
        $error = [
            'status' => '0',
            'fecha' => date('Y-m-d H:i:s'),
            'descripcion' => 'Error en la funcion canHaveLoan() al consumir el servicio.',
            'codigoError' => $t->getCode(),
            'msnError' => $t->getMessage(),
            'linea' => $t->getLine(),
            'archivo' => $t->getFile(),
            'requestLog' => $data,
            'responseLog' => $response,
        ];    
        Log::error('Error en la función save() del Servicio PreCargaPreDirectosService.', $error);
        return $error;

    }
}


}
